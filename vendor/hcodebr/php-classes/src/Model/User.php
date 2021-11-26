<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model{

	const SESSION = "User";

	const SECRET = "HcodePhp7_Secret";

	public static function login($login, $password)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0)
		{
			throw new \Exception("Usuario ou Senha Invalidos.");
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)

		{
			$user = new User();

			$user -> setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else{
			throw new \Exception("Usuario ou Senha Invalidos.");
		}
	}

	public static function verifyLogin($inadmin = true)
	{
		if(
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin

		)

		{
				header("Location: /admin/login/");
				exit;
		}
	}

	public static function logout()
	{

		$SESSION[User::SESSION] = NULL;
	}


	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select ("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
		
	}

	/*public function get($iduser)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser;", array(
			":iduser"=>$iduser

		));

		$data = $results[0];

		$this->setData($data);
	}*/

	public function save()
	{

		$sql = new Sql();
		
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function get($iduser)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(

			":iduser"=>$iduser
		));

		$data = $results[0];

		$this->setData($data);
	}

	public function update()
	{

		$sql = new Sql();
		
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$data = $results[0];

		$this->setData($data);
	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));

	}

	public static function getForgot($mail)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(tb_idperson) WHERE a.desemail = :email", array(

			"email"=>$email
		));

		if(count($results) ===0)
		{
			throw new \Exception("Nao foi possivel recuperar a senha.");
		}

		else
		{

			$data = $results2[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if(count($results2) ===0)
			{
				throw new \Exception("Nao foi possivel recuperar a senha");
				
			}

			else
			{
				$dataRecovery = $results2[0];
				
				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

				$link = "127.0.0.1:83/admin/forgot/reset?code=$code";
				
				$maier = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da PC GameStore", "forgot", array(

					"name"=>$data["desperson"],
					"link"=>$link

				));

				$mailer->send();

				return $data;
			}
		}

	}

	public static function validForgotDecrypt($code)
	{

		$idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_userpasswordrecoveries a INNER JOIN tb_users b USING(iduser) INNER JOIN tb_persons c USING(idperson) WHERE a.idrecovery = :idrecovery AND a.dtrecovery IS NULL AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()", array(
			":idrecovery"=>idrecovery
		));

		if(count($results) ===0)
		{
			throw new \Exception("Não foi possivel recuperar sua senha.");			
		}
		else{

			return $results[0];
		}
	}

	public static function setForgotUsed($idrecovery)
	{

		$sql = new Sql();

		$results = $sql->query("UPDATE tb_userpasswordrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(

			"idrecovery"=>$idrecovery

		));

	}

	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despasswords = :password WHERE iduser =:iduser", array(

			":password"=>$password,
			":user"=>$this->getiduser()

		));

	}

}



?>