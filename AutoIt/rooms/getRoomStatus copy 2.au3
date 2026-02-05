; ======================================================================
; NEHOPS ルームインジ（全館表示）をドラッグ選択→コピー→TXT保存→JSON生成→WEBアップロード
; Win7 / 低スペック向け：待ち多め・ログ多め
; ======================================================================

#include <Date.au3>
#include <MsgBoxConstants.au3>
#include <Clipboard.au3>
#include "wd_core.au3"
#include "wd_helper.au3"
#include "wd_capabilities.au3"

; ===================== パス設定 =====================
Global Const $NEHOPS_EXE = "C:\NEHOPS\ExecClient\bin\FWS90500_CL.exe"
Global Const $NEHOPS_DIR = "C:\NEHOPS\ExecClient\bin"

Global Const $LOG_DIR  = "C:\Users\PC008\Documents\autoit\logs"
Global Const $LOG_PATH = $LOG_DIR & "\roomindi_" & @YEAR & @MON & @MDAY & "_" & @HOUR & @MIN & @SEC & ".log"

Global Const $ROOMINDI_DIR      = "C:\Users\PC008\Documents\roomindi"
Global Const $ROOMINDI_JSON_DIR = "C:\Users\PC008\Documents\roomindi\jsons"

; ===================== WebDriver / サイトURL =====================
Global Const $CHROMEDRIVER = "C:\Tools\chromedriver_109\chromedriver.exe" ; Chrome 109 用
Global Const $LOGIN_URL    = "https://nch.nagoyacrown.co.jp/admin/"
Global Const $SUCCESS_URL  = "https://nch.nagoyacrown.co.jp/admin/banquet/"
Global Const $UP_URL       = "https://nch.nagoyacrown.co.jp/admin/guestrooms/jsonupload.php"


; ===================== 認証（secrets.ini があれば優先） =====================
Local $INI         = @ScriptDir & "\secrets.ini"

; NEHOPS
Local $NEHOPS_USER = IniRead($INI, "nehops", "user", "s035")
Local $NEHOPS_PASS = IniRead($INI, "nehops", "pass", "0515")

; WEB（secrets.ini 優先。無ければここは空にしてエラーにする）
Local $LOGIN_USER = IniRead($INI, "auth", "user", "takeichi@nagoyacrown.co.jp")
Local $LOGIN_PASS = IniRead($INI, "auth", "pass", "nCh@6633")

; ===================== セレクタ =====================
; ログイン（複数候補）
Global Const $SEL_USER = "#username"
Global Const $SEL_PASS = "#password"
Global Const $SEL_BTN  = "form#loginForm button[type='submit']"

; JSONアップロード（質問文のformを反映：name="jsonfile"）
Global Const $SEL_JSON_FILE   = "input[type='file'][name='jsonfile'], input[name='jsonfile'], input[type='file']"
Global Const $SEL_JSON_SUBMIT = "form#json_form button[type='submit'], #json_form button[type='submit'], button[type='submit'], input[type='submit']"

; 成功判定（rooms_jsonupload.php に表示される文言）
Global Const $UPLOAD_SUCCESS_TEXT = "アップロードしました"

; ヘッドレス
Global Const $HEADLESS_CHROME = True

; ===== 終了処理を必ず走らせるためのグローバル =====
Global $g_sSession = ""     ; WebDriver セッションID
Global $g_WebStarted = False
Global $g_NehopsStarted = False
Global $g_NehopsClosed = False
Global $g_WebClosed = False

; ★プロセス終了時に必ず呼ばれる
OnAutoItExitRegister("_ExitCleanup")


; ===================== 動作の安定化（低スペック向け） =====================
Opt("WinTitleMatchMode", 2)      ; タイトル部分一致
Opt("SendKeyDelay", 30)
Opt("SendKeyDownDelay", 10)
Opt("WinWaitDelay", 250)
Opt("MouseCoordMode", 1)         ; マウス座標：画面座標（ドラッグ固定座標のため）

; ===================== グローバル（後始末用） =====================
Global $g_WD_Session = ""        ; WebDriver セッション
Global $g_WD_Started = False     ; WebDriver 起動済み
Global $g_Nehops_Tried = False   ; NEHOPS 起動を試した（終了処理の目安）

; どんな終了でも Cleanup を走らせる（エラーで Exit しても走る）
OnAutoItExitRegister("CleanupOnExit")

; ======================================================================
; エントリーポイント
; ======================================================================
DirCreate($LOG_DIR)
DirCreate($ROOMINDI_DIR)
DirCreate($ROOMINDI_JSON_DIR)

_LogMsg("[INFO] START")
_LogMsg("[INFO] LOG_PATH=" & $LOG_PATH)
_LogMsg("[INFO] ROOMINDI_DIR=" & $ROOMINDI_DIR)
_LogMsg("[INFO] ROOMINDI_JSON_DIR=" & $ROOMINDI_JSON_DIR)

If Not FileExists($NEHOPS_EXE) Then
    Die("NEHOPS exe なし", $NEHOPS_EXE)
EndIf

If Not FileExists($CHROMEDRIVER) Then
    Die("chromedriver.exe なし", $CHROMEDRIVER)
EndIf

; メイン処理
If Not StartNehopsCopyJsonAndUpload() Then
    Die("処理失敗", "どこかで失敗しました。ログを確認してください。")
EndIf

_LogMsg("[INFO] DONE")
Exit 0


; ======================================================================
; ログ／エラー
; ======================================================================
Func _LogMsg($msg)
    DirCreate($LOG_DIR)
    FileWriteLine($LOG_PATH, _NowCalc() & " " & $msg)
EndFunc

Func Die($title, $msg)
    _LogMsg("[ERROR] " & $title & " - " & $msg)
    MsgBox($MB_ICONERROR, $title, $msg)
    Exit 1
EndFunc


; ======================================================================
; メイン：NEHOPS → TXT → JSON → WEBアップロード
; ======================================================================
Func StartNehopsCopyJsonAndUpload()
    ; ① NEHOPS 起動→ログイン→全館コピー→TXT→JSON
    Local $jsonPath = StartNehopsCopyAndJson()
    If $jsonPath = "" Then
        _LogMsg("[ERROR] JSON生成まで失敗")
        Return False
    EndIf

    ; ② WEBアップロード（ヘッドレス）
    If Not WebUploadJson($jsonPath) Then
        _LogMsg("[ERROR] WebUploadJson 失敗")
        Return False
    EndIf

    _LogMsg("[INFO] Upload completed OK: " & $jsonPath)
    Return True
EndFunc


; ======================================================================
; NEHOPS：起動→ログイン→全館コピー→TXT保存→JSON保存（成功時 jsonPath を返す）
; ======================================================================
Func StartNehopsCopyAndJson()
    $g_Nehops_Tried = True

    _LogMsg("[INFO] NEHOPS 起動: " & $NEHOPS_EXE)
    Run($NEHOPS_EXE, $NEHOPS_DIR)

    ; ① ログイン画面待ち → 入力 → OK
    If Not WinWait("ログイン", "", 30) Then
        _LogMsg("[ERROR] ログイン画面が出ません")
        Return ""
    EndIf

    If Not TypeLoginCreds($NEHOPS_USER, $NEHOPS_PASS) Then
        _LogMsg("[ERROR] ログイン入力に失敗")
        Return ""
    EndIf

    ; ② メニュー選択へ
    If Not WinWait("メニュー選択", "", 30) Then
        _LogMsg("[ERROR] メニュー選択が出ません")
        Return ""
    EndIf
    WinActivate("メニュー選択")
    WinWaitActive("メニュー選択", "", 10)
    Send("{ENTER}")
    Sleep(800)

    ; ③ NEHOPS メニューへ
    If Not WinWait("NEHOPS メニュー", "", 30) Then
        _LogMsg("[ERROR] NEHOPS メニューが出ません")
        Return ""
    EndIf
    WinActivate("NEHOPS メニュー")
    WinWaitActive("NEHOPS メニュー", "", 10)
    Sleep(500)

    ; ④ 全館表示→ドラッグ選択→コピー→TXT保存（成功時は txtパス）
    Local $txtPath = DragByFixedMousePosAndCopy()
    If $txtPath = "" Then
        _LogMsg("[ERROR] コピー→TXT保存に失敗")
        Return ""
    EndIf
    _LogMsg("[INFO] txtPath=" & $txtPath)

    ; ⑤ JSON化（成功時は jsonパス）
    Local $jsonPath = ConvertRoomIndiTxtToJson($txtPath)
    If $jsonPath = "" Then
        _LogMsg("[ERROR] JSON化に失敗。@error=" & @error)
        Return ""
    EndIf

    If Not FileExists($jsonPath) Then
        _LogMsg("[ERROR] JSONパスは返ったがファイルが存在しない: " & $jsonPath)
        Return ""
    EndIf

    _LogMsg("[INFO] JSON saved: " & $jsonPath)
    Return $jsonPath
EndFunc


; ======================================================================
; ログイン入力（できるだけ ControlSetText で安定化）
; ======================================================================
Func FindFirstHandle($hWnd, $aPatterns)
    For $i = 0 To UBound($aPatterns) - 1
        Local $h = ControlGetHandle($hWnd, "", $aPatterns[$i])
        If $h <> "" Then Return $h
    Next
    Return 0
EndFunc

Func TypeLoginCreds($user, $pass)
    Local $hLogin = WinWait("[TITLE:ログイン]", "", 30)
    If $hLogin = 0 Then Return SetError(1, 0, False)

    WinActivate($hLogin)
    WinWaitActive($hLogin, "", 10)

    Local $aUserCandidates[3] = ["[CLASS:Edit; INSTANCE:1]", _
                                 "[CLASS:WindowsForms10.EDIT.app.*; INSTANCE:1]", _
                                 "[CLASS:TEdit; INSTANCE:1]"]
    Local $aPassCandidates[3] = ["[CLASS:Edit; INSTANCE:2]", _
                                 "[CLASS:WindowsForms10.EDIT.app.*; INSTANCE:2]", _
                                 "[CLASS:TEdit; INSTANCE:2]"]
    Local $aOkCandidates[4]   = ["[CLASS:Button; TEXT:OK]", _
                                 "[CLASS:Button; INSTANCE:1]", _
                                 "[CLASS:TButton; INSTANCE:1]", _
                                 "[CLASS:WindowsForms10.BUTTON.app.*; INSTANCE:1]"]

    Local $hUser = FindFirstHandle($hLogin, $aUserCandidates)
    Local $hPass = FindFirstHandle($hLogin, $aPassCandidates)

    If $hUser = 0 Or $hPass = 0 Then
        _LogMsg("[WARN] Login ClassNN が見つからないためフォールバック入力")
        ControlFocus($hLogin, "", "")
        Send($user)
        Send("{TAB}")
        Send($pass)
        Send("{ENTER}")
        Return True
    EndIf

    ControlFocus($hLogin, "", $hUser)
    ControlSetText($hLogin, "", $hUser, "")
    ControlSetText($hLogin, "", $hUser, $user)

    ControlFocus($hLogin, "", $hPass)
    ControlSetText($hLogin, "", $hPass, "")
    ControlSetText($hLogin, "", $hPass, $pass)

    Local $hOk = FindFirstHandle($hLogin, $aOkCandidates)
    If $hOk <> 0 Then
        ControlClick($hLogin, "", $hOk)
    Else
        _LogMsg("[WARN] OKボタンが見つからないため Enter で送信")
        ControlSend($hLogin, "", $hPass, "{ENTER}")
    EndIf

    Sleep(800)
    Return True
EndFunc


; ======================================================================
; ルームインジ（全館）をドラッグ選択→コピー→TXT保存
; 画面座標: (45,245) → (45,655) を縦ドラッグ
; ======================================================================
Func DragByFixedMousePosAndCopy()
    DirCreate($ROOMINDI_DIR)

    WinActivate("NEHOPS メニュー")
    WinWaitActive("NEHOPS メニュー", "", 10)

    ; ルームインジ → 全館表示
    Send("!{F7}")
    Sleep(2500)
    Send("!3")
    Sleep(2500)

    ; フォーカス
    MouseClick("left", 45, 245, 1)
    Sleep(200)

    ; ドラッグ選択（縦）
    MouseClickDrag("left", 45, 245, 45, 655, 15)
    Sleep(400)

    ; コピー
    Send("^c")
    Sleep(700)

    Local $txt = ClipGet()
    If $txt = "" Then
        _LogMsg("[WARN] clipboard empty after drag")
        Return ""
    EndIf

    Local $out = $ROOMINDI_DIR & "\roomindi_" & @YEAR & @MON & @MDAY & "_" & @HOUR & @MIN & @SEC & ".txt"
    Local $w = FileWrite($out, $txt & @CRLF)

    If $w = 0 Then
        _LogMsg("[ERROR] TXT書き込み失敗: " & $out)
        Return ""
    EndIf

    _LogMsg("[INFO] TXT saved: " & $out & " len=" & StringLen($txt) & " writeRet=" & $w)
    Return $out
EndFunc


; ======================================================================
; JSON化：TXT -> JSON
; 「部屋番号とステータスが改行で分断」されるため状態機械で拾う
; ======================================================================
Func ParseDateTimeFromFilename($path, ByRef $outDate, ByRef $outTime)
    Local $name = StringTrimLeft($path, StringInStr($path, "\", 0, -1))
    Local $m = StringRegExp($name, "(\d{8})_(\d{6})", 1)
    If @error Or UBound($m) < 2 Then Return False

    Local $ymd = $m[0]
    Local $hms = $m[1]
    $outDate = StringLeft($ymd, 4) & "-" & StringMid($ymd, 5, 2) & "-" & StringMid($ymd, 7, 2)
    $outTime = StringLeft($hms, 2) & ":" & StringMid($hms, 3, 2)
    Return True
EndFunc

Func _JsonEscape($s)
    $s = StringReplace($s, "\", "\\")
    $s = StringReplace($s, '"', '\"')
    $s = StringReplace($s, @CR, "")
    $s = StringReplace($s, @LF, "\n")
    Return $s
EndFunc

Func _FindOrCreateFloorDict(ByRef $dict, $floorNo)
    If Not $dict.Exists($floorNo) Then
        Local $a[0][2] ; rooms: [n][2] (room,status)
        $dict.Add($floorNo, $a)
    EndIf
EndFunc

Func _AddRoom(ByRef $dict, $floorNo, $roomNo, $status)
    _FindOrCreateFloorDict($dict, $floorNo)
    Local $a = $dict.Item($floorNo)
    Local $n = UBound($a, 1)
    ReDim $a[$n + 1][2]
    $a[$n][0] = $roomNo
    $a[$n][1] = $status
    $dict.Item($floorNo) = $a
EndFunc

Func _SortKeysNumeric(ByRef $aKeys)
    Local $i, $j
    For $i = 0 To UBound($aKeys) - 2
        For $j = $i + 1 To UBound($aKeys) - 1
            If Number($aKeys[$i]) > Number($aKeys[$j]) Then
                Local $t = $aKeys[$i]
                $aKeys[$i] = $aKeys[$j]
                $aKeys[$j] = $t
            EndIf
        Next
    Next
EndFunc

Func ConvertRoomIndiTxtToJson($txtPath, $jsonPath = "")
    _LogMsg("[INFO] ConvertRoomIndiTxtToJson begin: " & $txtPath)

    If Not FileExists($txtPath) Then
        _LogMsg("[ERROR] txtが存在しない: " & $txtPath)
        Return SetError(1, 0, "")
    EndIf

    Local $date = "", $time = ""
    If Not ParseDateTimeFromFilename($txtPath, $date, $time) Then
        _LogMsg("[ERROR] ファイル名からdate/timeを取れない: " & $txtPath)
        Return SetError(2, 0, "")
    EndIf
    _LogMsg("[INFO] date=" & $date & " time=" & $time)

    DirCreate($ROOMINDI_JSON_DIR)

    If $jsonPath = "" Then
        Local $base = StringTrimLeft($txtPath, StringInStr($txtPath, "\", 0, -1))
        $base = StringRegExpReplace($base, "\.txt$", ".json")
        $jsonPath = $ROOMINDI_JSON_DIR & "\" & $base
    EndIf
    _LogMsg("[INFO] jsonPath=" & $jsonPath)

    Local $text = FileRead($txtPath)
    If $text = "" Then
        _LogMsg("[ERROR] txtが空、または読み込み失敗: " & $txtPath)
        Return SetError(3, 0, "")
    EndIf

    $text = StringReplace($text, @CRLF, @LF)
    $text = StringReplace($text, @CR, @LF)
    $text = StringReplace($text, '"', "")
    $text = StringReplace($text, @TAB, " ")

    Local $lines = StringSplit($text, @LF, 1)

    Local $dict = ObjCreate("Scripting.Dictionary")
    If Not IsObj($dict) Then
        _LogMsg("[ERROR] Scripting.Dictionary を作れません（COM無効の可能性）")
        Return SetError(10, 0, "")
    EndIf

    Local $curFloor = ""
    Local $pendingRoom = ""
    Local $pairsTotal = 0

    For $i = 1 To $lines[0]
        Local $ln = StringStripWS($lines[$i], 3)
        If $ln = "" Then ContinueLoop

        Local $fm = StringRegExp($ln, "^\s*(\d{3})\b", 1)
        If Not @error And IsArray($fm) Then
            $curFloor = $fm[0]
            $pendingRoom = ""
        EndIf

        If $curFloor = "" Then ContinueLoop

        Local $tokens = StringRegExp($ln, "(\d{4}|[A-Z0-9]{1,6})", 3)
        If @error Or Not IsArray($tokens) Then ContinueLoop

        For $t = 0 To UBound($tokens) - 1
            Local $tk = $tokens[$t]

            If StringRegExp($tk, "^\d{4}$") Then
                $pendingRoom = $tk
                ContinueLoop
            EndIf

            If $pendingRoom <> "" Then
                Local $st = StringUpper($tk)
                $st = StringRegExpReplace($st, "[^A-Z0-9]", "")
                _AddRoom($dict, $curFloor, $pendingRoom, $st)
                $pairsTotal += 1
                $pendingRoom = ""
            EndIf
        Next
    Next

    _LogMsg("[INFO] parsed pairs total=" & $pairsTotal & " floors=" & $dict.Count)

    If $dict.Count = 0 Or $pairsTotal = 0 Then
        _LogMsg("[ERROR] rooms/status が1件も解析できませんでした")
        Return SetError(20, 0, "")
    EndIf

    Local $json = "{"
    $json &= @LF & '  "date": "' & $date & '",'
    $json &= @LF & '  "time": "' & $time & '",'
    $json &= @LF & '  "floor": ['

    Local $aKeys = $dict.Keys()
    _SortKeysNumeric($aKeys)

    For $k = 0 To UBound($aKeys) - 1
        Local $fno = $aKeys[$k]
        Local $rooms = $dict.Item($fno)

        If $k > 0 Then $json &= ","
        $json &= @LF & "    {"
        $json &= @LF & '      "number": "' & $fno & '",'
        $json &= @LF & '      "rooms": ['

        For $r = 0 To UBound($rooms, 1) - 1
            If $r > 0 Then $json &= ","
            $json &= @LF & '        { "room": "' & _JsonEscape($rooms[$r][0]) & '", "status": "' & _JsonEscape($rooms[$r][1]) & '" }'
        Next

        $json &= @LF & "      ]"
        $json &= @LF & "    }"
    Next

    $json &= @LF & "  ]"
    $json &= @LF & "}" & @LF

    Local $w = FileWrite($jsonPath, $json)
    _LogMsg("[INFO] FileWrite(json) ret=" & $w & " jsonLen=" & StringLen($json))

    If $w = 0 Then
        _LogMsg("[ERROR] JSON書き込み失敗: " & $jsonPath)
        Return SetError(9, 0, "")
    EndIf

    Return $jsonPath
EndFunc


; ======================================================================
; WEBアップロード（ヘッドレス）：LOGIN_URL → SUCCESS_URL確認 → UP_URL → JSONアップロード → 成功文言確認
; ======================================================================
Func WebUploadJson($jsonPath)
    _LogMsg("[INFO] WebUploadJson begin: " & $jsonPath)

    If $LOGIN_USER = "" Or $LOGIN_PASS = "" Then
        _LogMsg("[ERROR] WEBログインID/パスワードが未設定です（secrets.ini の [auth] を確認）")
        Return False
    EndIf

    If Not FileExists($jsonPath) Then
        _LogMsg("[ERROR] jsonが存在しない: " & $jsonPath)
        Return False
    EndIf

    ; --- WebDriver起動 ---
    _WD_Option('Driver', $CHROMEDRIVER)
    _WD_Option('Port', 9515)
    _WD_Option('DriverParams', '--verbose --log-path="' & @ScriptDir & '\chromedriver.log"')

    Local $r = _WD_Startup()
    $g_WD_Started = True
    _LogMsg("[INFO] _WD_Startup ret=" & $r)

    _WD_CapabilitiesStartup()
    _WD_CapabilitiesAdd('alwaysMatch', 'chrome')
    _WD_CapabilitiesAdd('w3c', True)
    _WD_CapabilitiesAdd('args', '--window-size=1280,900')

    If $HEADLESS_CHROME Then
        _WD_CapabilitiesAdd('args', '--headless')
        _WD_CapabilitiesAdd('args', '--disable-gpu')
    EndIf

    Local $caps = _WD_CapabilitiesGet()
    $g_WD_Session = _WD_CreateSession($caps)
    If @error Or $g_WD_Session = "" Then
        _LogMsg("[ERROR] _WD_CreateSession 失敗 @error=" & @error)
        Return False
    EndIf
    _LogMsg("[INFO] WD session created")

    ; --- LOGIN_URLへ ---
    _WD_Navigate($g_WD_Session, $LOGIN_URL)
    _WD_LoadWait($g_WD_Session, 250, 30000)

    ; 要素待ち
    Local $eUser = WaitAnySelector($g_WD_Session, $SEL_USER, 25000)
    Local $ePass = WaitAnySelector($g_WD_Session, $SEL_PASS, 25000)
    Local $eBtn  = WaitAnySelector($g_WD_Session, $SEL_BTN , 25000)

    If (Not $eUser) Or (Not $ePass) Or (Not $eBtn) Then
        _LogMsg("[ERROR] ログイン要素が見つかりません")
        Return False
    EndIf

    _WD_ElementAction($g_WD_Session, $eUser, 'value', $LOGIN_USER)
    _WD_ElementAction($g_WD_Session, $ePass, 'value', $LOGIN_PASS)
    _WD_ElementAction($g_WD_Session, $eBtn , 'click')
    _WD_LoadWait($g_WD_Session, 250, 30000)

    ; SUCCESS_URL への遷移確認
    If Not WaitUrlContains($g_WD_Session, $SUCCESS_URL, 30000, 300) Then
        _LogMsg("[ERROR] ログイン後にSUCCESS_URLになりません: " & $SUCCESS_URL)
        Return False
    EndIf
    _LogMsg("[INFO] Login OK → SUCCESS_URL")

    ; --- UP_URLへ ---
    _WD_Navigate($g_WD_Session, $UP_URL)
    _WD_LoadWait($g_WD_Session, 250, 30000)

    ; ファイル入力取得
    Local $eFile = WaitAnySelector($g_WD_Session, $SEL_JSON_FILE, 25000)
    If Not $eFile Then
        _LogMsg("[ERROR] JSONファイル入力が見つかりません")
        Return False
    EndIf

    ; ファイルパスを設定（value→sendkeysの順で試す）
    If Not SetFileInput($g_WD_Session, $eFile, $jsonPath) Then
        _LogMsg("[ERROR] ファイル入力にパスを設定できませんでした: " & $jsonPath)
        Return False
    EndIf

    ; 送信ボタン取得＆クリック
    Local $eSubmit = WaitAnySelector($g_WD_Session, $SEL_JSON_SUBMIT, 25000)
    If Not $eSubmit Then
        _LogMsg("[ERROR] JSON送信ボタンが見つかりません")
        Return False
    EndIf

    _WD_ElementAction($g_WD_Session, $eSubmit, 'click')
    _WD_LoadWait($g_WD_Session, 250, 30000)

    ; 成功文言をページ本文から検出
    If Not WaitBodyContainsText($g_WD_Session, $UPLOAD_SUCCESS_TEXT, 30000, 400) Then
        _LogMsg("[ERROR] 成功文言が確認できません: " & $UPLOAD_SUCCESS_TEXT)
        Return False
    EndIf

    _LogMsg("[INFO] Upload success text detected: " & $UPLOAD_SUCCESS_TEXT)
    Return True
EndFunc


; ======================================================================
; WebDriver 補助
; ======================================================================
Func WaitAnySelector($sSession, $sCSSList, $iTimeout = 15000, $iInterval = 250)
    Local $aList = StringSplit($sCSSList, ",")
    Local $t = TimerInit()

    While TimerDiff($t) < $iTimeout
        For $i = 1 To $aList[0]
            Local $css = StringStripWS($aList[$i], 3)
            If $css = "" Then ContinueLoop
            Local $h = _WD_FindElement($sSession, $_WD_LOCATOR_ByCSSSelector, $css)
            If @error = $_WD_ERROR_Success And $h <> "" Then Return $h
        Next
        Sleep($iInterval)
    WEnd
    Return 0
EndFunc

Func WaitUrlContains($sSession, $sNeedle, $iTimeout = 15000, $iInterval = 250)
    Local $t = TimerInit()
    While TimerDiff($t) < $iTimeout
        Local $url = _WD_ExecuteScript($sSession, "return location.href;")
        If StringInStr($url, $sNeedle, 0) > 0 Then Return True
        Sleep($iInterval)
    WEnd
    Return False
EndFunc

Func FileChosen($sSession, $eFile)
    Local $js = "var el=arguments[0]; return el && el.files && el.files.length>0;"
    Local $ret = _WD_ExecuteScript($sSession, $js, $eFile)
    Return ($ret = True)
EndFunc

Func SetFileInput($sSession, $eFile, $sPath)
    ; まず value で試す
    _WD_ElementAction($sSession, $eFile, 'value', $sPath)
    Sleep(200)
    If FileChosen($sSession, $eFile) Then Return True

    ; ダメなら sendkeys
    _WD_ElementAction($sSession, $eFile, 'sendkeys', $sPath)
    Sleep(200)
    If FileChosen($sSession, $eFile) Then Return True

    Return False
EndFunc

Func WaitBodyContainsText($sSession, $needle, $iTimeout = 20000, $iInterval = 300)
    Local $t = TimerInit()
    While TimerDiff($t) < $iTimeout
        Local $txt = _WD_ExecuteScript($sSession, "return document.body ? document.body.innerText : '';")
        If StringInStr($txt, $needle, 0) > 0 Then Return True
        Sleep($iInterval)
    WEnd
    Return False
EndFunc


; ======================================================================
; NEHOPS 終了
; ======================================================================
Func CloseNehops()
    ; 画面が無い/アクティブでない等で失敗することがあるので、安全に
    _LogMsg("[INFO] Closing NEHOPS (Alt+F4)")
    WinActivate("NEHOPS メニュー")
    Sleep(200)

    Send("!{F4}")
    Sleep(300)
    ; 終了確認が出る想定：TAB→ENTER
    Send("{TAB}")
    Sleep(200)
    Send("{ENTER}")
    Sleep(400)
EndFunc


; ======================================================================
; どんな終了でも実行：NEHOPS終了・WebDriverセッション削除・WebDriver停止
; ======================================================================
Func CleanupOnExit()
    _LogMsg("[INFO] === Exit Cleanup Start ===")

    ; 1) WebDriver セッション削除 → Shutdown（ヘッドレスを確実に閉じる）
    If $g_WD_Session <> "" Then
        _LogMsg("[INFO] WD delete session")
        _WD_DeleteSession($g_WD_Session)
        $g_WD_Session = ""
    EndIf

    If $g_WD_Started Then
        _LogMsg("[INFO] WD shutdown")
        _WD_Shutdown()
        $g_WD_Started = False
    EndIf

    ; 2) NEHOPS を閉じる（起動を試した場合のみ）
    If $g_Nehops_Tried Then
        ; ウィンドウが存在する時だけ終了処理
        If WinExists("NEHOPS メニュー") Then
            CloseNehops()
        ElseIf WinExists("メニュー選択") Then
            WinActivate("メニュー選択")
            Send("!{F4}")
        ElseIf WinExists("ログイン") Then
            WinActivate("ログイン")
            Send("!{F4}")
        EndIf
    EndIf

    _LogMsg("[INFO] === Exit Cleanup End ===")
EndFunc

Func _ExitCleanup()
    ; Exit中にさらに落ちないように保護
    Local $e = @error

    _LogMsg("[INFO] === Exit Cleanup Start ===")

    ; 1) ブラウザ（WebDriver）を先に閉じる（画面フォーカス等に影響しない）
    If Not $g_WebClosed Then
        _LogMsg("[INFO] Closing WebDriver")
        _CloseWebDriverSafe()
        $g_WebClosed = True
    EndIf

    ; 2) NEHOPSを閉じる
    If Not $g_NehopsClosed Then
        _LogMsg("[INFO] Closing NEHOPS (Alt+F4)")
        CloseNehops()
        $g_NehopsClosed = True
    EndIf

    _LogMsg("[INFO] === Exit Cleanup End ===")

    SetError($e)
EndFunc

Func _CloseWebDriverSafe()
    ; 例外で止めない：全部握りつぶして進める
    If $g_sSession <> "" Then
        _WD_DeleteSession($g_sSession)
        $g_sSession = ""
    EndIf
    _WD_Shutdown()
EndFunc
