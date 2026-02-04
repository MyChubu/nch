; ======================================================================
; NEHOPS ルームインジ（全館表示）をドラッグ選択→コピー→TXT保存→JSON生成
; Win7 / 低スペック向け：待ち多め・ログ多め・正規表現の重い処理は使わない
; ======================================================================

#include <Date.au3>
#include <MsgBoxConstants.au3>
#include <Clipboard.au3>

; ===================== パス設定 =====================
Global Const $NEHOPS_EXE = "C:\NEHOPS\ExecClient\bin\FWS90500_CL.exe"
Global Const $NEHOPS_DIR = "C:\NEHOPS\ExecClient\bin"

Global Const $LOG_DIR  = "C:\Users\PC008\Documents\autoit\logs"
Global Const $LOG_PATH = $LOG_DIR & "\roomindi_" & @YEAR & @MON & @MDAY & "_" & @HOUR & @MIN & @SEC & ".log"

Global Const $ROOMINDI_DIR      = "C:\Users\PC008\Documents\roomindi"
Global Const $ROOMINDI_JSON_DIR = "C:\Users\PC008\Documents\roomindi\jsons"

; ===================== NEHOPS 認証（secrets.ini があれば優先） =====================
Local $INI         = @ScriptDir & "\secrets.ini"
Local $NEHOPS_USER = IniRead($INI, "nehops", "user", "s035")
Local $NEHOPS_PASS = IniRead($INI, "nehops", "pass", "0515")

; ===================== 動作の安定化（低スペック向け） =====================
Opt("WinTitleMatchMode", 2)      ; タイトル部分一致
Opt("SendKeyDelay", 30)
Opt("SendKeyDownDelay", 10)
Opt("WinWaitDelay", 250)
Opt("MouseCoordMode", 1)         ; マウス座標：画面座標（ドラッグ固定座標のため）

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

If Not StartNehopsCopyAndJson() Then
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
; メイン：NEHOPS 起動→ログイン→全館コピー→TXT保存→JSON保存
; ======================================================================
Func StartNehopsCopyAndJson()
    _LogMsg("[INFO] NEHOPS 起動: " & $NEHOPS_EXE)
    Run($NEHOPS_EXE, $NEHOPS_DIR)

    ; ① ログイン画面待ち → 入力 → OK
    If Not WinWait("ログイン", "", 30) Then
        _LogMsg("[ERROR] ログイン画面が出ません")
        Return False
    EndIf

    If Not TypeLoginCreds($NEHOPS_USER, $NEHOPS_PASS) Then
        _LogMsg("[ERROR] ログイン入力に失敗")
        Return False
    EndIf

    ; ② メニュー選択へ
    If Not WinWait("メニュー選択", "", 30) Then
        _LogMsg("[ERROR] メニュー選択が出ません")
        Return False
    EndIf
    WinActivate("メニュー選択")
    WinWaitActive("メニュー選択", "", 10)
    Send("{ENTER}")
    Sleep(800)

    ; ③ NEHOPS メニューへ
    If Not WinWait("NEHOPS メニュー", "", 30) Then
        _LogMsg("[ERROR] NEHOPS メニューが出ません")
        Return False
    EndIf
    WinActivate("NEHOPS メニュー")
    WinWaitActive("NEHOPS メニュー", "", 10)
    Sleep(500)

    ; ④ 全館表示→ドラッグ選択→コピー→TXT保存（成功時は txtパスを返す）
    Local $txtPath = DragByFixedMousePosAndCopy()
    If $txtPath = "" Then
        _LogMsg("[ERROR] コピー→TXT保存に失敗")
        Return False
    EndIf
    _LogMsg("[INFO] txtPath=" & $txtPath)

    ; ⑤ JSON化（成功時は jsonパスを返す）
    Local $jsonPath = ConvertRoomIndiTxtToJson($txtPath)
    If $jsonPath = "" Then
        _LogMsg("[ERROR] JSON化に失敗。@error=" & @error)
        Return False
    EndIf

    If Not FileExists($jsonPath) Then
        _LogMsg("[ERROR] JSONパスは返ったがファイルが存在しない: " & $jsonPath)
        Return False
    EndIf

    ; ★ここで NEHOPS を閉じる
    CloseNehops()

    _LogMsg("[INFO] JSON saved: " & $jsonPath)
    Return True
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
; ★重要：NEHOPSのコピーは「部屋番号とステータスが改行で分断」されるため、
;        正規表現を重く使わず「状態機械」で拾う（4桁→次のステータス）
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

    ; 改行を統一
    $text = StringReplace($text, @CRLF, @LF)
    $text = StringReplace($text, @CR, @LF)

    ; 余計なダブルクォートは消す（"2001 などが混ざるため）
    $text = StringReplace($text, '"', "")

    ; タブはスペース化（トークン分割を安定させる）
    $text = StringReplace($text, @TAB, " ")

    ; 1行ずつ処理
    Local $lines = StringSplit($text, @LF, 1)

    ; floor -> rooms の格納
    Local $dict = ObjCreate("Scripting.Dictionary")
    If Not IsObj($dict) Then
        _LogMsg("[ERROR] Scripting.Dictionary を作れません（COM無効の可能性）")
        Return SetError(10, 0, "")
    EndIf

    Local $curFloor = ""
    Local $pendingRoom = "" ; 「部屋番号を見つけたが、まだステータスが来ていない」状態

    Local $pairsTotal = 0

    For $i = 1 To $lines[0]
        Local $ln = StringStripWS($lines[$i], 3)
        If $ln = "" Then ContinueLoop

        ; 行頭に3桁があれば「フロア行」とみなす（030 / 029 など）
        Local $fm = StringRegExp($ln, "^\s*(\d{3})\b", 1)
        If Not @error And IsArray($fm) Then
            $curFloor = $fm[0]
            ; フロアが切り替わったら、保留中の部屋番号は捨てる（安全策）
            $pendingRoom = ""
        EndIf

        ; フロアが未確定なら解析しない
        If $curFloor = "" Then ContinueLoop

        ; 行内の「4桁」または「ステータスっぽい英数(1-6)」を順に拾う
        ; 例：2001 / XN / OPN / VPU など
        Local $tokens = StringRegExp($ln, "(\d{4}|[A-Z0-9]{1,6})", 3)
        If @error Or Not IsArray($tokens) Then ContinueLoop

        For $t = 0 To UBound($tokens) - 1
            Local $tk = $tokens[$t]

            ; 4桁 → 部屋番号として保留
            If StringRegExp($tk, "^\d{4}$") Then
                $pendingRoom = $tk
                ContinueLoop
            EndIf

            ; ステータス → 直前に部屋番号があればペア成立
            If $pendingRoom <> "" Then
                ; ステータスは大文字英数だけに整形
                Local $st = StringUpper($tk)
                $st = StringRegExpReplace($st, "[^A-Z0-9]", "")

                _AddRoom($dict, $curFloor, $pendingRoom, $st)
                $pairsTotal += 1
                $pendingRoom = ""
            EndIf
        Next
    Next

    _LogMsg("[INFO] parsed pairs total=" & $pairsTotal & " floors=" & $dict.Count)

    ; 何も取れなかったら失敗扱い（空JSON防止）
    If $dict.Count = 0 Or $pairsTotal = 0 Then
        _LogMsg("[ERROR] rooms/status が1件も解析できませんでした")
        Return SetError(20, 0, "")
    EndIf

    ; ---- JSON組み立て ----
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

    ; JSON書き込み（戻り値チェック）
    Local $w = FileWrite($jsonPath, $json)
    _LogMsg("[INFO] FileWrite(json) ret=" & $w & " jsonLen=" & StringLen($json))

    If $w = 0 Then
        _LogMsg("[ERROR] JSON書き込み失敗（権限/パス/フォルダ）: " & $jsonPath)
        Return SetError(9, 0, "")
    EndIf

    If Not FileExists($jsonPath) Then
        _LogMsg("[ERROR] JSONを書いたはずだが存在しない: " & $jsonPath)
        Return SetError(11, 0, "")
    EndIf

    Return $jsonPath
EndFunc

; ===================== NEHOPS 終了 =====================
Func CloseNehops()
    _LogMsg("[INFO] NEHOPS 終了処理")
    Send("!{F4}")
    Send("{TAB}")
    Send("{ENTER}")
EndFunc

