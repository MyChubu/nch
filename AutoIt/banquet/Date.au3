#include <Date.au3>
#include <Array.au3>
#include "wd_core.au3"
#include "wd_helper.au3"
#include "wd_capabilities.au3"
#include <MsgBoxConstants.au3>
#include <ScreenCapture.au3>

; ===================== ユーザー設定／保存先 =====================
Global Const $SCHE_DIR   = "C:\Users\PC006\Documents\csv\sche\"
Global Const $CHARGE_DIR = "C:\Users\PC006\Documents\csv\charge\"

; WebDriver / サイトURL
Global Const $CHROMEDRIVER = "C:\Tools\chromedriver_109\chromedriver.exe" ; Chrome 109 用
Global Const $LOGIN_URL    = "https://nch.nagoyacrown.co.jp/admin/login.php"
Global Const $SUCCESS_URL  = "https://nch.nagoyacrown.co.jp/admin/"
Global Const $SCHE_URL     = "https://nch.nagoyacrown.co.jp/admin/banquet/sys_csvupload.php"
Global Const $CHARGE_URL   = "https://nch.nagoyacrown.co.jp/admin/banquet/sys_csv_charge_upload.php"

; セレクタ（必要に応じて調整）
Global Const $SEL_USER   = "#login_id, input[name='login_id'], input[name='username'], #username"
Global Const $SEL_PASS   = "#password, input[name='password'], input[type='password']"
Global Const $SEL_BTN    = "button[type='submit'], input[type='submit'], #loginBtn"
Global Const $SEL_FILE   = "input[type='file'], #csvfile, input[name='csv'], #file"
Global Const $SEL_SUBMIT = "button#upload, input#upload, button[type='submit'], input[type='submit']"

Global Const $EXPORT_TIMEOUT_MS       = 30000 ; 出力待ち最大(ミリ秒)
Global Const $POST_WRITE_SLEEP_OK_MS  = 1500   ; 出力成功時だけ少し待つ（1.5秒）

; 動作フラグ・ログ
Global Const $HEADLESS_CHROME   = True    ; Web操作はヘッドレス（109世代で可）
Global Const $RETRY_UPLOAD_ONCE = True    ; 各アップロードを1回リトライ
Global Const $LOG_PATH          = @ScriptDir & "\nehops_" & @YEAR & @MON & @MDAY & ".log"
Global $_WD_DEBUG               = $_WD_DEBUG_None

; 認証情報（secrets.ini 優先、無ければ直書き）
Local $INI        = @ScriptDir & "\secrets.ini"
Local $LOGIN_USER = IniRead($INI, "auth", "user", "")
Local $LOGIN_PASS = IniRead($INI, "auth", "pass", "")
If $LOGIN_USER = "" Then $LOGIN_USER = "websystem@nagoyacrown.co.jp" ; ←必要なら変更
If $LOGIN_PASS = "" Then $LOGIN_PASS = "q4So-^1@"                   ; ←必要なら変更

Local $NEHOPS_USER = IniRead($INI, "nehops", "user", "")
Local $NEHOPS_PASS = IniRead($INI, "nehops", "pass", "")
Local $NEHOPS_SCHE = IniRead($INI, "nehops", "sche", "")
Local $NEHOPS_CHRG = IniRead($INI, "nehops", "chrg", "")
If $NEHOPS_USER = "" Then $NEHOPS_USER = "s035" ; ←必要なら変更
If $NEHOPS_PASS = "" Then $NEHOPS_PASS = "0515" ; ←必要なら変更
If $NEHOPS_SCHE = "" Then $NEHOPS_SCHE = "87"
If $NEHOPS_CHRG = "" Then $NEHOPS_CHRG = "79"

; ===================== 日付と連番ファイル名の準備 =====================
Local $sNow  = StringFormat("%04d%02d%02d%02d%02d%02d", @YEAR, @MON, @MDAY, @HOUR, @MIN, @SEC)
Local $today = _NowCalc() ; "YYYY/MM/DD HH:MM:SS"

; 開始日：10日前
Local $startTS = _DateAdd('D', -5, $today)

; 終了日：3年後の“その月の月末日”
Global Const $HORIZON_YEARS = 3  ; ここだけ変えればOK
Local $plusN  = _DateAdd('Y', $HORIZON_YEARS, $today)
Local $yrN    = StringLeft($plusN, 4)
Local $moN    = StringMid($plusN, 6, 2)
Local $nextMN = _DateAdd('M', 1, $yrN & "/" & $moN & "/01 00:00:00")
Local $endTS  = _DateAdd('D', -1, $nextMN)

; ===================== ユーティリティ =====================
Func _LogMsg($msg)
    FileWriteLine($LOG_PATH, _NowCalc() & " " & $msg)
EndFunc

Func Die($title, $msg)
    _LogMsg("[ERROR] " & $title & " - " & $msg)
    MsgBox(16, $title, $msg)
    Exit 1
EndFunc

Func IsInteractiveSession()
    Local $s = EnvGet("SESSIONNAME")
    If $s = "" Or StringInStr($s, "Service") Then Return False
    Return True
EndFunc

; ファイル生成待ち
Func WaitFileAppears($path, $iTimeout = 60000, $iInterval = 500)
    Local $t = TimerInit()
    While TimerDiff($t) < $iTimeout
        If FileExists($path) Then Return True
        Sleep($iInterval)
    WEnd
    Return False
EndFunc

Func YmdFromTS($ts) ; "YYYY/MM/DD HH:MM:SS" -> "YYYYMMDD"
    Return StringReplace(StringLeft($ts, 10), "/", "")
EndFunc

; 指定TSの属する月から nヶ月後の「月末」
Func MonthEndAfterN($ts, $nMonths)
    Local $y  = StringLeft($ts, 4)
    Local $m  = StringMid($ts, 6, 2)
    Local $nm = _DateAdd('M', $nMonths + 1, $y & "/" & $m & "/01 00:00:00")
    Return _DateAdd('D', -1, $nm)
EndFunc

; ts1 <= ts2 ？
Func TS_LE($ts1, $ts2)
    Return (_DateDiff('s', $ts1, $ts2) >= 0)
EndFunc


; ===================== アプリ操作（ログイン1回→チャンク毎に出力） =====================
Func StartNehopsAndLogin()

    _LogMsg("[INFO] NEHOPS 起動")
    Run('C:\NEHOPS\ExecClient\bin\FWS90500_CL.exe','c:\NEHOPS\ExecClient\bin')

    WinWait("ログイン","",10)
    If Not TypeLoginCreds($NEHOPS_USER, $NEHOPS_PASS) Then
		Die("ログイン失敗", "ログイン画面の操作に失敗しました。ClassNN を確認してください。")
	EndIf

    ; メニューへ
    WinWait("メニュー選択","",10)
    WinActivate("メニュー選択")
    Send("{TAB}")
    Send("{ENTER}")

    WinWait("NEHOPS メニュー","",10)
    WinActivate("NEHOPS メニュー")

    ; 機能番号入力画面へ（従来踏襲）
    Send("{ALT}")
    Send("{TAB 5}")
    Send("{ENTER}")
    Send("{TAB}")
    Send("{ENTER}")
    Sleep(2000)
	;Send("{ENTER}")

    _LogMsg("[INFO] ログイン完了（機能番号入力待ち）")
    Return True
EndFunc

; ===== 追加: 便利関数 =====
Func FindFirstHandle($hWnd, $aPatterns)
    For $i = 0 To UBound($aPatterns) - 1
        Local $h = ControlGetHandle($hWnd, "", $aPatterns[$i])
        If $h <> "" Then Return $h
    Next
    Return 0
EndFunc

; ===== 置き換え版: NEHOPS ログイン入力 =====
; 以前の ControlSend + Send("{ENTER}") は全削除して、これに置き換え
Func TypeLoginCreds($user, $pass)
    Opt("WinTitleMatchMode", 2) ; 部分一致
    Local $hLogin = WinWait("[TITLE:ログイン]", "", 10)
    If $hLogin = 0 Then Return SetError(1, 0, False)
    WinActivate($hLogin)
    WinWaitActive($hLogin, "", 5)

    ; よくあるコントロールの並びを順に試す（必要に応じて追記）
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
        ; 最低限のフォールバック：Tabで移動してから ControlSetText
        _LogMsg("[WARN] ClassNN が見つからないためフォールバックを使用")
        ControlFocus($hLogin, "", "")
        ; ユーザー名（フォーカス前提）
        Send("^a")
        Send("{DEL}")
        Send($user)
        ; パスワードへ（Tabで移動）
        Send("{TAB}")
        Send("^a")
        Send("{DEL}")
        Send($pass)
        ; OK
        Send("{ENTER}")
        Return True
    EndIf

    ; ユーザー名を直接設定
    ControlFocus($hLogin, "", $hUser)
    ControlSetText($hLogin, "", $hUser, "") ; クリア
    ControlSetText($hLogin, "", $hUser, $user)

    ; パスワードを直接設定
    ControlFocus($hLogin, "", $hPass)
    ControlSetText($hLogin, "", $hPass, "") ; クリア
    ControlSetText($hLogin, "", $hPass, $pass)

    ; OK/ログイン ボタンをクリック（Enterは禁止）
    Local $hOk = FindFirstHandle($hLogin, $aOkCandidates)
    If $hOk <> 0 Then
        ControlClick($hLogin, "", $hOk)
    Else
        ; ボタンが取れない場合のみ最後の手段として Enter
        _LogMsg("[WARN] OKボタンが見つからないため Enter で送信")
        ControlSend($hLogin, "", $hPass, "{ENTER}")
    EndIf
    Return True
EndFunc



; 1チャンク分（スケジュール→戻る→チャージ→戻る）
Func ExportOneChunkInSession($fromYmd, $toYmd, $outSche, $outCharge)
    _LogMsg("[INFO] Export chunk: " & $fromYmd & " - " & $toYmd)

    ; --- スケジュール ---
    Send("{ENTER}")
	Sleep(500)
	Send("{ENTER}")
	Sleep(500)
	Send("{ENTER}")
    ControlSend("", "", "", $NEHOPS_SCHE)
    Send("{ENTER 3}")
    Sleep(3000)
    Send("!{5}")
    Send("{ENTER}")
    Sleep(2000)
    ControlSend("", "", "", $outSche)
    Send("{ENTER}")
    ControlSend("", "", "", $fromYmd)
    Send("{ENTER}")
    ControlSend("", "", "", $toYmd)
    Send("{ENTER}")
    Send("!{E}")
	WinWait("宴会ＣＳＶ出力 ファイル出力画面","",10)
    WinActivate("宴会ＣＳＶ出力 ファイル出力画面")
    Send("{TAB}")
    Send("{ENTER}")
    WinWait("認証処理","",10)
    WinActivate("認証処理")
    ControlSend("", "", "", $NEHOPS_PASS)
    Send("{ENTER}")

    Local $scheReady = WaitFileAppears($outSche, $EXPORT_TIMEOUT_MS, 500)
	If Not $scheReady Then
		_LogMsg("[WARN] スケジュールCSV 出力待ちタイムアウト: " & $outSche)
	Else
		Sleep($POST_WRITE_SLEEP_OK_MS) ; 成功時のみ軽く待つ
	EndIf

    ; --- 戻る ---
    Send("!{B}")
    Send("{TAB}")
    Send("{ENTER}")
    Sleep(500)

    ; --- チャージ ---
    Send("{ENTER}")
	Sleep(500)
	Send("{ENTER}")
	Sleep(500)
	Send("{ENTER}")
    ControlSend("", "", "", $NEHOPS_CHRG)
    Send("{ENTER 3}")
    Sleep(3000)
    Send("!{5}")
    Send("{ENTER}")
    Sleep(2000)
    ControlSend("", "", "", $outCharge)
    Send("{ENTER}")
    ControlSend("", "", "", $fromYmd)
    Send("{ENTER}")
    ControlSend("", "", "", $toYmd)
    Send("{ENTER}")
    Send("!{E}")
	WinWait("宴会ＣＳＶ出力 ファイル出力画面","",10)
    WinActivate("宴会ＣＳＶ出力 ファイル出力画面")
    Send("{TAB}")
    Send("{ENTER}")
    WinWait("認証処理","",10)
    WinActivate("認証処理")
    ControlSend("", "", "", $NEHOPS_PASS)
    Send("{ENTER}")

    Local $chargeReady = WaitFileAppears($outCharge, $EXPORT_TIMEOUT_MS, 500)
	If Not $chargeReady Then
		_LogMsg("[WARN] チャージCSV 出力待ちタイムアウト: " & $outCharge)
	Else
		Sleep($POST_WRITE_SLEEP_OK_MS)
	EndIf

    ; --- 戻る（次チャンク準備） ---
    Send("!{B}")
    Send("{TAB}")
    Send("{ENTER}")
    Sleep(500)

    _LogMsg("[INFO] Export chunk 完了: " & $fromYmd & " - " & $toYmd)
    Return True
EndFunc

Func CloseNehops()
    _LogMsg("[INFO] NEHOPS 終了処理")
    Send("!{F4}")
    Send("{TAB}")
    Send("{ENTER}")
EndFunc

; ===================== WebDriver 補助 =====================
Func WaitAnySelector($sSession, $sCSSList, $iTimeout = 15000, $iInterval = 250)
    Local $aList = StringSplit($sCSSList, ",")
    Local $hTimer = TimerInit()
    While TimerDiff($hTimer) < $iTimeout
        For $i = 1 To $aList[0]
            Local $sCss = StringStripWS($aList[$i], 3)
            If $sCss = "" Then ContinueLoop
            Local $h = _WD_FindElement($sSession, $_WD_LOCATOR_ByCSSSelector, $sCss)
            If @error = $_WD_ERROR_Success And $h <> "" Then Return $h
        Next
        Sleep($iInterval)
    WEnd
    Return 0
EndFunc

Func WaitUrlContains($sSession, $sNeedle, $iTimeout = 15000, $iInterval = 250)
    Local $hTimer = TimerInit()
    While TimerDiff($hTimer) < $iTimeout
        Local $sUrl = _WD_ExecuteScript($sSession, "return location.href;")
        If StringInStr($sUrl, $sNeedle, 0) > 0 Then Return True
        Sleep($iInterval)
    WEnd
    Return False
EndFunc

Func EnsureVisible($sSession, $sCss)
    Local $js = _
        "var el = document.querySelector(`" & $sCss & "`);" & _
        "if(!el) return false;" & _
        "var s = el.style;" & _
        "s.display='block'; s.visibility='visible'; s.opacity=1; s.width='1px'; s.height='1px';" & _
        "el.removeAttribute('hidden');" & _
        "return true;"
    _WD_ExecuteScript($sSession, $js)
EndFunc

Func FileChosen($sSession, $eFile)
    Local $js = "var el=arguments[0]; return el && el.files && el.files.length>0;"
    Local $ret = _WD_ExecuteScript($sSession, $js, $eFile)
    Return ($ret = True)
EndFunc

Func SetFileInput($sSession, $eFile, $sPath)
    _WD_ElementAction($sSession, $eFile, 'value', $sPath)
    If FileChosen($sSession, $eFile) Then Return True
    _WD_ElementAction($sSession, $eFile, 'sendkeys', $sPath)
    If FileChosen($sSession, $eFile) Then Return True
    Return False
EndFunc

Func WaitForSuccess($sSession, $aCssList, $aXpathList, $iTimeout = 60000, $iInterval = 300)
    Local $hTimer = TimerInit()
    While TimerDiff($hTimer) < $iTimeout
        For $i = 0 To UBound($aCssList) - 1
            Local $css = StringStripWS($aCssList[$i], 3)
            If $css <> "" Then
                Local $h = _WD_FindElement($sSession, $_WD_LOCATOR_ByCSSSelector, $css)
                If @error = $_WD_ERROR_Success And $h <> "" Then Return True
            EndIf
        Next
        For $i = 0 To UBound($aXpathList) - 1
            Local $xp = StringStripWS($aXpathList[$i], 3)
            If $xp <> "" Then
                Local $h = _WD_FindElement($sSession, $_WD_LOCATOR_ByXPath, $xp)
                If @error = $_WD_ERROR_Success And $h <> "" Then Return True
            EndIf
        Next
        Sleep($iInterval)
    WEnd
    Return False
EndFunc

Func DefaultScheSuccessCss()
    Local $a[6] = [ _
        ".alert-success", _
        ".alert.alert-success", _
        "#flash-success", _
        ".uk-alert-success", _
        ".notice-success", _
        ".upload-result" _
    ]
    Return $a
EndFunc

Func DefaultChargeSuccessCss()
    Local $a[6] = [ _
        ".alert-success", _
        ".alert.alert-success", _
        "#flash-success", _
        ".uk-alert-success", _
        ".notice-success", _
        ".upload-result" _
    ]
    Return $a
EndFunc

Func DefaultSuccessXpath()
    Local $a[5] = [ _
        "//*[contains(text(),'アップロード') and (contains(text(),'完了') or contains(text(),'成功'))]", _
        "//*[contains(text(),'取り込み') and (contains(text(),'完了') or contains(text(),'成功'))]", _
        "//*[contains(text(),'インポート') and (contains(text(),'完了') or contains(text(),'成功'))]", _
        "//*[contains(@class,'alert') and (contains(.,'完了') or contains(.,'成功'))]", _
        "//*[contains(@id,'result') and (contains(.,'完了') or contains(.,'成功'))]" _
    ]
    Return $a
EndFunc

Func Cleanup(ByRef $sSession)
    If $sSession <> "" Then
        _WD_DeleteSession($sSession)
        $sSession = ""
    EndIf
    _WD_Shutdown()
EndFunc

Func UploadCsv($sSession, $sUrl, $sFilePath, $sFileSelector, $sSubmitSelector, $aCssSuccess, $aXpathSuccess, $iTimeout = 60000, $iInterval = 300)
    _LogMsg("[INFO] Upload: " & $sFilePath & " -> " & $sUrl)
    _WD_Navigate($sSession, $sUrl)
    _WD_LoadWait($sSession, 250, 20000)

    EnsureVisible($sSession, $sFileSelector)
    Local $eFile = WaitAnySelector($sSession, $sFileSelector, 15000)
    If Not $eFile Then Return SetError(1, 0, False)

    If Not SetFileInput($sSession, $eFile, $sFilePath) Then Return SetError(2, 0, False)

    Local $eSubmit = WaitAnySelector($sSession, $sSubmitSelector, 15000)
    If Not $eSubmit Then Return SetError(3, 0, False)

    _WD_ElementAction($sSession, $eSubmit, 'click')
    _WD_LoadWait($sSession, 250, 20000)

    If WaitForSuccess($sSession, $aCssSuccess, $aXpathSuccess, $iTimeout, $iInterval) Then
        _LogMsg("[INFO] Upload success: " & $sFilePath)
        Return True
    EndIf
    _LogMsg("[WARN] Upload success UI not detected: " & $sFilePath)
    Return False
EndFunc

; ===================== メイン処理（取得→即アップロードを繰り返す） =====================

; 保存先ディレクトリを用意
DirCreate($SCHE_DIR)
DirCreate($CHARGE_DIR)

; アプリ：起動＆ログイン（1回だけ）
If Not StartNehopsAndLogin() Then
    Die("NEHOPS 起動不可", "画面がない、またはログインに失敗しました。")
EndIf

; ===== WebDriver 起動＆ログイン（1回だけ） =====
If Not FileExists($CHROMEDRIVER) Then
    CloseNehops()
    Die("Chromedriverなし", "指定パスに chromedriver.exe がありません: " & $CHROMEDRIVER)
EndIf

_WD_Option('Driver', $CHROMEDRIVER)
_WD_Option('Port', 9515)
_WD_Option('DriverParams', '--verbose --log-path="' & @ScriptDir & '\chromedriver.log"')
_WD_Startup()

_WD_CapabilitiesStartup()
_WD_CapabilitiesAdd('alwaysMatch', 'chrome')
_WD_CapabilitiesAdd('w3c', True)
_WD_CapabilitiesAdd('args', '--window-size=1280,900')
If $HEADLESS_CHROME Then
    _WD_CapabilitiesAdd('args', '--headless')
    _WD_CapabilitiesAdd('args', '--disable-gpu')
EndIf
Local $sCaps    = _WD_CapabilitiesGet()
Local $sSession = _WD_CreateSession($sCaps)
If @error Or $sSession = "" Then
    CloseNehops()
    Die("起動失敗", "Chrome セッションを開始できませんでした。")
EndIf

If $LOGIN_USER = "" Or $LOGIN_PASS = "" Then
    CloseNehops()
    Cleanup($sSession)
    Die("未設定", "ログインID/パスワードが未設定です。secrets.ini か直書きで設定してください。")
EndIf

_WD_Navigate($sSession, $LOGIN_URL)
_WD_LoadWait($sSession, 250, 20000)
Local $eUser = WaitAnySelector($sSession, $SEL_USER, 20000)
Local $ePass = WaitAnySelector($sSession, $SEL_PASS, 20000)
Local $eBtn  = WaitAnySelector($sSession, $SEL_BTN , 20000)
If Not $eUser Or Not $ePass Or Not $eBtn Then
    CloseNehops()
    Cleanup($sSession)
    Die("要素なし", "ログイン画面の要素（ID/Pass/ボタン）が見つかりません。")
EndIf
_WD_ElementAction($sSession, $eUser, 'value', $LOGIN_USER)
_WD_ElementAction($sSession, $ePass, 'value', $LOGIN_PASS)
_WD_ElementAction($sSession, $eBtn , 'click')
If Not WaitUrlContains($sSession, $SUCCESS_URL, 20000, 300) Then
    CloseNehops()
    Cleanup($sSession)
    Die("ログイン失敗", "ログイン後に " & $SUCCESS_URL & " へ遷移しませんでした。")
EndIf

; ===== 3か月チャンクで：取得→即アップロード（連番） =====
Local $i = 1
Local $curTS = $startTS

While TS_LE($curTS, $endTS)
    ; このチャンクの終了＝開始月から+2か月の月末（=3か月）
    Local $chunkEndTS = MonthEndAfterN($curTS, 2)
    If Not TS_LE($chunkEndTS, $endTS) Then $chunkEndTS = $endTS

    Local $fromYmd = YmdFromTS($curTS)
    Local $toYmd   = YmdFromTS($chunkEndTS)

    Local $schePath   = $SCHE_DIR   & "sche-"   & $sNow & "_" & $i & ".csv"
    Local $chargePath = $CHARGE_DIR & "charge-" & $sNow & "_" & $i & ".csv"

    ; ① NEHOPSでこのチャンクのCSV生成（ログイン済みセッションを使い回し）
	Local $ok = ExportOneChunkInSession($fromYmd, $toYmd, $schePath, $chargePath)

	; 生成結果の有無を判定
	Local $hasSche   = FileExists($schePath)
	Local $hasCharge = FileExists($chargePath)

	; ★スケジュール欠落のときは両方アップロードしない（スキップ）
	If Not $hasSche Then
		_LogMsg(StringFormat("[WARN] スケジュール欠落のため、このチャンクはアップロードを行いません: %s-%s 連番 %d (charge=%s)", _
			$fromYmd, $toYmd, $i, $hasCharge ? "OK" : "NG"))
		; 次チャンクへ（ここで自前で進めてから ContinueLoop）
		$curTS = _DateAdd('D', 1, $chunkEndTS)
		$i += 1
		ContinueLoop
	EndIf

	; ★両方欠落のときもスキップ
	If (Not $hasSche) And (Not $hasCharge) Then
		_LogMsg(StringFormat("[WARN] 両CSV欠落のためスキップ: %s-%s 連番 %d", $fromYmd, $toYmd, $i))
		$curTS = _DateAdd('D', 1, $chunkEndTS)
		$i += 1
		ContinueLoop
	EndIf

	; ② 条件付きアップロード
	If $hasSche And (Not $hasCharge) Then
		_LogMsg(StringFormat("[WARN] チャージ欠落のためスケジュールのみアップロード: %s-%s 連番 %d", $fromYmd, $toYmd, $i))

		; --- スケジュールのみ ---
		Local $okUp = UploadCsv($sSession, $SCHE_URL, $schePath, $SEL_FILE, $SEL_SUBMIT, DefaultScheSuccessCss(), DefaultSuccessXpath(), 60000)
		If (Not $okUp) And $RETRY_UPLOAD_ONCE Then
			_LogMsg("[INFO] リトライ: " & $schePath)
			$okUp = UploadCsv($sSession, $SCHE_URL, $schePath, $SEL_FILE, $SEL_SUBMIT, DefaultScheSuccessCss(), DefaultSuccessXpath(), 60000)
		EndIf
		If Not $okUp Then
			; 失敗を致命にしない場合は下3行を削除し、ログだけにしてください
			CloseNehops()
			Cleanup($sSession)
			Die("アップロード失敗", "スケジュールCSV: " & $schePath)
		EndIf

	Else
		; --- 両方あり：通常アップロード（スケジュール→チャージ） ---
		Local $okUp = UploadCsv($sSession, $SCHE_URL, $schePath, $SEL_FILE, $SEL_SUBMIT, DefaultScheSuccessCss(), DefaultSuccessXpath(), 60000)
		If (Not $okUp) And $RETRY_UPLOAD_ONCE Then
			_LogMsg("[INFO] リトライ: " & $schePath)
			$okUp = UploadCsv($sSession, $SCHE_URL, $schePath, $SEL_FILE, $SEL_SUBMIT, DefaultScheSuccessCss(), DefaultSuccessXpath(), 60000)
		EndIf
		If Not $okUp Then
			CloseNehops()
			Cleanup($sSession)
			Die("アップロード失敗", "スケジュールCSV: " & $schePath)
		EndIf

		$okUp = UploadCsv($sSession, $CHARGE_URL, $chargePath, $SEL_FILE, $SEL_SUBMIT, DefaultChargeSuccessCss(), DefaultSuccessXpath(), 60000)
		If (Not $okUp) And $RETRY_UPLOAD_ONCE Then
			_LogMsg("[INFO] リトライ: " & $chargePath)
			$okUp = UploadCsv($sSession, $CHARGE_URL, $chargePath, $SEL_FILE, $SEL_SUBMIT, DefaultChargeSuccessCss(), DefaultSuccessXpath(), 60000)
		EndIf
		If Not $okUp Then
			CloseNehops()
			Cleanup($sSession)
			Die("アップロード失敗", "チャージCSV: " & $chargePath)
		EndIf
	EndIf

	_LogMsg(StringFormat("[INFO] チャンク処理完了（連番 %d）: %s - %s", $i, $fromYmd, $toYmd))


    ; 次チャンク開始＝今回の終了日の翌日
    $curTS = _DateAdd('D', 1, $chunkEndTS)
    $i += 1
WEnd

; 終了処理
CloseNehops()
Cleanup($sSession)
_LogMsg("[INFO] 全チャンク：取得→即アップロード 完了")
; MsgBox(64, "Done", "全処理完了")
