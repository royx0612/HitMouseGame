<?php
### Session 尚未啟動的話
if (session_id() == '') {
  # 將它啟動
  session_start();
}

/*  
  CSRF Generator 
  本類別兩個方法用來產生及驗證 csrf
  csrf 產生 - generateToken():string
  csrf 驗證 - validateToken(string $form_token):bool
  介紹:
  1. 透過產生 64 位元的亂數存在 SESSION 的 csrf_token 也放在 Form 的表單中
  2. 然後在驗證時候，頁面中去比對 SESSION 的 csrf_token
  3. 比對成功 回傳 true，失敗 false
*/
class CSRF
{
  /* 建構函式 */
  public function __contruct()
  {
  }

  /* 產生 csrf_token */
  static public function generateToken(string $tokenName): string
  {
    // 產生 csrf token
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION[$tokenName] = $csrf_token;
    return $csrf_token;
  }

  /* 產生 csrf_token 並且產生 input:hidden tag */
  static public function generateTokenWithInputHidden(string $tokenName): string
  {
    $csrf_token = self::generateToken($tokenName);
    return sprintf("<input type='hidden' name='%s' value='%s'>", $tokenName, $csrf_token);
  }

  /* 驗證 csrf_token，確認合法來源 */
  static public function validateToken(string $tokenName, string $formToken): bool
  {
    if (
      isset($_SESSION[$tokenName]) && // session 沒有 csrf_token 
      $_SESSION[$tokenName] == $formToken //兩者比對不相符
    ) {
      // 回傳 true
      return true;
    } else {
      // 刪除 session 中的 csrf_token
      // unset($_SESSION['csrf_token']);
      // 回傳 false
      return false;
    }
  }
}
