<?php
  class Security
  {
    public function checkLogin($id, $password)
    {
      $pdo = getPdo();
      $stmt = $pdo->query("SELECT * FROM user WHERE id = $id");
      foreach ($stmt as $row)
      {
          return password_verify($row['pwsalt'].$password, $row['password']);
      }
    }

    static public function checkToken($user, $token)
    {
      $pdo = getPdo();
      $stmt = $pdo->query("SELECT token, tokensalt, tokenexpire FROM user WHERE id = $user");
      foreach ($stmt as $row)
      {
        $now = new datetime(date("Y-m-d H:i:s"));
        $expires = new datetime($row['tokenexpire']);
        if(($expires > $now) && password_verify($token.$row['tokensalt'], $row['token'])){
          return true;
        }
      }
    }

    static public function newToken($userid, $password)
    {
      if(self::checkLogin($userid, $password)) {
        $pdo = getPdo();
        $expire = new datetime(date("Y-m-d H:i:s"));
        $expire->add(new DateInterval('PT1H'));
        $data['date'] = $expire->format("Y-m-d H:i:s");
        $tokensalt = password_hash(self::getguid(), PASSWORD_DEFAULT);
        $token = password_hash(self::getguid(), PASSWORD_DEFAULT);
        $tokencrypt = password_hash($token.$tokensalt, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE user SET token = ?, tokensalt = ?, tokenexpire = ? WHERE id = ?;");
        $stmt->execute([$tokencrypt,$tokensalt,$expire->format("Y-m-d H:i:s"),$userid]);
        return $token;
      }
    }
    
    static public function randomStr(){
      return self::getguid();
    }

    private function getguid()
    {
      // OSX/Linux
      if (function_exists('openssl_random_pseudo_bytes') === true) {
          $data = openssl_random_pseudo_bytes(16);
          $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
          $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
          return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
      }
    }
  }