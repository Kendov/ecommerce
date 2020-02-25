<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {

    const SESSION = "User";
    const SECRET = "teste_secret_token";
    const SECRET_IV = "teste_secret_token_IV";

    public static function getFromSession(){
        $user = new User();

        if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

            $user->setData($_SESSION[User::SESSION]);
            
        }
        
        return $user;
    }

    public static function checkLogin($inadmin = true){
        if(
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ){
            return false;
        }else{
            if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){
                return true;
            }else if($inadmin === false){
                return true;
            }else{
                return false;
            }
        }
    }

    public static function login($login, $password){
        $sql = new Sql;

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", [
            ":LOGIN"=>$login
        ]);

        if(count($results) === 0){
            throw new \Exception("user invalid or don`t exist", 1);
            
        }

        $data = $results[0];

        if(\password_verify($password, $data["despassword"])){
            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;

        }else{
            throw new \Exception("password invalid or don`t exist", 1);
            
        }
    }

    public static function verifyLogin($inadmin = true){

        if(User::checkLogin($inadmin) === false){
            header("Location: /admin/login");
            exit;
        }

    }

    public static function logout(){
        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll(){
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save(){
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ]);

        $this->setData($results[0]);
    }

    public function get($iduser){
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", [
            ":iduser"=> $iduser
        ]);
        
        $this->setData($results[0]);
    }

    public function update(){
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ]);

        $this->setData($results[0]);
    }

    public function delete(){
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", [
            ":iduser"=> $this->getiduser()
        ]);
    }

    public static function getForgot($email){
        $sql = new Sql();
        $results = $sql->select("
            SELECT * FROM tb_persons a 
            INNER JOIN tb_users b USING(idperson) 
            WHERE a.desemail = :desemail", 
            [
            ":desemail"=>$email
            ]
        );

        if(count($results) === 0){
            throw new Exception("unable to retrieve the password");
            
        }else{
            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",[
                ":iduser"=> $data["iduser"],
                ":desip"=>$_SERVER["REMOTE_ADDR"]
            ]);

            if(count($results2) === 0){
                throw new Exception("unable to retrieve the password", 1);
                
            }else{
                $dataRecovery = $results2[0];

                $code = openssl_encrypt(
                    $dataRecovery['idrecovery'], 
                    'AES-128-CBC', 
                    pack("a16", User::SECRET), 
                    0, 
                    pack("a16", User::SECRET_IV)
                );

                $code = base64_encode($code);

                $link = "http://www.ecommerce.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer(
                    $data["desemail"], 
                    $data["desperson"], 
                    "reset password Ecommerce store", 
                    "forgot",
                    [
                        "name"=>$data["desperson"],
                        "link"=>$link
                    ]
                );
                $mailer->send();

                return $data;
            }
        }
    }


    public static function validForgotDecrypt($code){
        $code = \base64_decode($code);

        $idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

        $sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
            a.idrecovery = :idrecovery
            AND
            a.dtrecovery IS NULL
            AND
            DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();",
            [
                ":idrecovery"=>$idrecovery
            ]
        );

        if(count($results) === 0){
            throw new Exception("Unable to recover password", 1);
            
        }else{
            return $results[0];
        }
    }



    public static function setForgotUsed($idrecovery){
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",[
            ":idrecovery"=>$idrecovery
        ]);
    }

    public function setPassword($password){
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", [
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ]);
    }

}

?>