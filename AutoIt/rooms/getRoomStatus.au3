#include <Date.au3>
#include <Array.au3>
#include "wd_core.au3"
#include "wd_helper.au3"
#include "wd_capabilities.au3"
#include <MsgBoxConstants.au3>
#include <ScreenCapture.au3>

; ===================== ユーザー設定／保存先 =====================
Global Const $DATA_DIR   = "C:\Users\PC008\Documents\nehops\rooms\"

; WebDriver / サイトURL
Global Const $CHROMEDRIVER = "C:\Tools\chromedriver_109\chromedriver.exe" ; Chrome 109 用
Global Const $LOGIN_URL    = "https://nch.nagoyacrown.co.jp/admin/"
Global Const $SUCCESS_URL  = "https://nch.nagoyacrown.co.jp/admin/banquet/"
Global Const $UPLOAD_URL   = "https://nch.nagoyacrown.co.jp/admin/rooms/dataupload.php"

; セレクタ（必要に応じて調整）
Global Const $SEL_USER   = "#login_id, input[name='login_id'], input[name='username'], #username"
Global Const $SEL_PASS   = "#password, input[name='password'], input[type='password']"
Global Const $SEL_BTN    = "button[type='submit'], input[type='submit'], #loginBtn"
Global Const $SEL_FILE   = "input[type='file'], #csvfile, input[name='csv'], #file"
Global Const $SEL_SUBMIT = "button#upload, input#upload, button[type='submit'], input[type='submit']"

; 認証情報（secrets.ini 優先、無ければ直書き）
Local $INI        = @ScriptDir & "\secrets.ini"
Local $LOGIN_USER = IniRead($INI, "auth", "user", "")
Local $LOGIN_PASS = IniRead($INI, "auth", "pass", "")
If $LOGIN_USER = "" Then $LOGIN_USER = "takeichi@nagoyacrown.co.jp" ; ←必要なら変更
If $LOGIN_PASS = "" Then $LOGIN_PASS = "nCh@6633"                   ; ←必要なら変更

Local $NEHOPS_USER = IniRead($INI, "nehops", "user", "")
Local $NEHOPS_PASS = IniRead($INI, "nehops", "pass", "")
If $NEHOPS_USER = "" Then $NEHOPS_USER = "s035" ; ←必要なら変更
If $NEHOPS_PASS = "" Then $NEHOPS_PASS = "0515" ; ←必要なら変更


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
    Send("{ENTER}")

    WinWait("NEHOPS メニュー","",10)
    WinActivate("NEHOPS メニュー")

    ; 機能番号入力画面へ（従来踏襲）
    Send("{ALT F7}")
    Sleep(2000)
	;Send("{ALT 3}")

    _LogMsg("[INFO] ログイン完了（機能番号入力待ち）")
    Return True
EndFunc