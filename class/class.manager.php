<?php
class manager
{
	private $database;

	/* **************************************** */

	function __construct ($database)
	{
		$this->database = $database;
	}

	function __destruct () {
		unset($this->users, $this->domains, $this->database);
	}

	/* **************************************** */

	function addUser ($username, $password, $fullname, $email, $description, $level, $active, $maxdomains)
	{
		if($_SESSION['level']<10)
		{
			throw new Exception("No rights");
			return false;
		}

		$query = "INSERT INTO `users` ( `id` , `username` , `password` , `fullname` , `email` , `description` , `level` , `active` , `maxdomains`) VALUES
		('', '".$this->database->escape_string($username)."', '".$this->database->escape_string($password)."', '".$this->database->escape_string($fullname)."',
		'".$this->database->escape_string($email)."', '".$this->database->escape_string($description)."', '".$this->database->escape_string($level)."',
		'".$this->database->escape_string($active)."', '".$this->database->escape_string($maxdomains)."');";

		if($this->database->query_master($query))
		{
			return mysql_insert_id();
		}else
		{
			throw new Exception($this->database->error());
		}
	}

	function getUser ($userId)
	{
		$query = "SELECT * FROM users WHERE id = '".$this->database->escape_string($userId)."'";
		$query = $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			return $this->database->fetch_array($query);
		}
	}

	function updateUser ($orgUserId, $userId, $username, $password, $fullname, $email, $description, $level, $active, $maxdomains)
	{
		$query = "UPDATE `users`
		SET `username`='".$this->database->escape_string($username)."',
		`fullname`='".$this->database->escape_string($fullname)."', `email`='".$this->database->escape_string($email)."',
		`description`='".$this->database->escape_string($description)."',";

		if($_SESSION['level']>5)
		{
			$query .= " `level`='".$this->database->escape_string($level)."', `active`='".$this->database->escape_string($active)."', `maxdomains`='".$this->database->escape_string($maxdomains)."',";
		}

		if($password!="")
		{
			$query .= " `password`='".$this->database->escape_string(md5($password))."',";
		}

		$query .= " `id`='".$this->database->escape_string($userId)."'
		WHERE `id`='".$this->database->escape_string($orgUserId)."' LIMIT 1;";

		if($_SESSION['level']<5 && $_SESSION['userId']!=$orgUserId || $_SESSION['level']>=5)
		{
			if($this->database->query_master($query))
			{
				return true;
			}else
			{
				throw new Exception ($this->database->error());
			}
		}
	}

	function removeUser ($userId)
	{
		if($_SESSION['level']>=5)
		{
			$query = "DELETE FROM `users` WHERE `id`='".$this->database->escape_string($userId)."' LIMIT 1;";

			if($this->database->query_master($query))
			{
				return true;
			}else
			{
				throw new Exception ($this->database->error());
			}
		}
	}

	function removeUserData ($userId)
	{
		if($_SESSION['level']>=5)
		{

			$query = "DELETE FROM zones z, domains d, records r USING zones z, domains d, records r
			WHERE z.domain_id = d.id AND
			z.domain_id = r.domain_id AND
			z.owner = '".$this->database->escape_string($userId)."';";

			if($this->database->query_master($query))
			{
				return true;
			}else
			{
				throw new Exception ($this->database->error());
			}
		}
	}

	/* **************************************** */

	function addZone ($domainId, $userId, $comment)
	{
		if($_SESSION['level']<5)
		{
			$userId = $_SESSION['userId'];
		}

		$query = "INSERT INTO `zones` ( `id` , `domain_id` , `owner` , `comment` )
		VALUES ( NULL , '".$this->database->escape_string($domainId)."', '".$this->database->escape_string($userId)."', '".$this->database->escape_string($comment)."' );";

		if($this->database->query_master($query))
		{
			return mysql_insert_id();
		}else
		{
			throw new Exception($this->database->error());
		}
	}

	function editZone ($domainId, $userId)
	{
		$query = "UPDATE `zones` SET owner='".$this->database->escape_string($userId)."'  WHERE `domain_id` = '".$this->database->escape_string($domainId)."'";

		if($_SESSION['level']<5)
		{
			$query .= " AND owner = '".$this->database->escape_string($_SESSION['userId'])."'";
		}

		$query .= " LIMIT 1;";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeZone ($zoneId)
	{
		$query = "DELETE FROM `zones` WHERE `id` = '".$this->database->escape_string($zoneId)."'";

		if($_SESSION['level']<5)
		{
			$query .= " AND owner = '".$this->database->escape_string($_SESSION['userId'])."'";
		}

		$query .= " LIMIT 1;";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeZoneByDomainId ($domainId)
	{
		$query = "DELETE FROM `zones` WHERE `domain_id` = '".$this->database->escape_string($domainId)."'";

		if($_SESSION['level']<5)
		{
			$query .= " AND owner = '".$this->database->escape_string($_SESSION['userId'])."'";
		}

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	/* **************************************** */

	function getDomain ($domainId)
	{
		$query = "SELECT * FROM domains WHERE id='".$this->database->escape_string($domainId)."'";

		$query = $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			return $this->database->fetch_array($query);
		}
	}

	function addDomain ($name, $master, $lastCheck, $type, $notifiedSerial, $account)
	{
		if($_SESSION['level'] == 1)
		{
			if(!$this->canAddDomainCheckMax($_SESSION['userId']))
			{
				throw new Exception("Max domain setting reached. Please ask your host to update your max domains setting.");
				$error = 1;
			}
		}

		if($error != 1)
		{
			$query = "INSERT INTO `domains` ( `id` , `name` , `master` , `last_check` , `type` , `notified_serial` , `account` ) VALUES
			('', '".$this->database->escape_string(trim($name))."', '".$this->database->escape_string($master)."' , '".$this->database->escape_string($lastCheck)."' ,
			'".$this->database->escape_string($type)."', '".$this->database->escape_string($notifiedSerial)."' , '".$this->database->escape_string($account)."');";

			if($this->database->query_master($query))
			{
				return mysql_insert_id();
			}else
			{
				throw new Exception($this->database->error());
			}
		}
	}

	function updateDomain ($orgDomainId, $domainId, $name, $master, $lastCheck, $type, $notifiedSerial, $account)
	{
		$query = "UPDATE `domains` SET `id` = '".$this->database->escape_string($domainId)."', `name` = '".$this->database->escape_string($name)."',
		`master` = '".$this->database->escape_string($master)."', `last_check` = '".$this->database->escape_string($lastCheck)."',
		`type` = '".$this->database->escape_string($type)."', `notified_serial` = '".$this->database->escape_string($notifiedSerial)."',
		`account` = '".$this->database->escape_string($account)."'
		WHERE `id` = '".$this->database->escape_string($orgDomainId)."' LIMIT 1;";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeDomain ($domainId)
	{
		$query = "DELETE FROM `domains` WHERE `id`='".$this->database->escape_string($domainId)."' LIMIT 1;";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function canAddDomainCheckMax ($userId)
	{
		$query = "SELECT count(owner) AS current FROM zones WHERE owner = ".$this->database->escape_string($userId);
		$query	= $this->database->query_slave($query) or die ($this->database->error());
		$record = $this->database->fetch_array($query);

		$user = $this->getUser($userId);

		if($record['current'] < $user['maxdomains'] || $user['maxdomains'] == 0)
		{
			return true;
		}

		return false;
	}

	function searchDomains ($q)
	{
		if(strlen($q)<2) // SEARCHES SMALLER THAN 2 ARE USELESS AND TAKE UP CPU :@
		{
			return '';
		}

		$return = array();
		$query	= "SELECT d.id, d.name, count(r.id) AS records, fullname, u.id AS userId
		FROM domains d, records r, zones z, users u
		WHERE d.id=r.domain_id AND
		d.id = z.domain_id AND
		z.owner = u.id AND";

		if($_SESSION['level']==1)
		{
			$query .= " z.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " d.name LIKE '%".addslashes($q)."%'
		GROUP BY r.domain_id
		ORDER BY name";
		$query	= $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			while($record=$this->database->fetch_array($query))
			{
				$return[] = $record;
			}

			return $return;
		}
	}

	function getListByLetter ($letter)
	{
		$query = "SELECT d.id, d.name, d.name REGEXP '^".$letter."' AS regex, count(r.id) AS records, fullname, u.id AS userId
		FROM domains d, records r, zones z, users u
		WHERE d.id=r.domain_id AND
		d.id = z.domain_id AND";

		if($_SESSION['level']==1)
		{
			$query .= " z.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " z.owner = u.id
		GROUP BY r.domain_id
		HAVING regex = 1
		ORDER BY name;";
		$query = $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			while($record=$this->database->fetch_array($query))
			{
				$return[] = $record;
			}

			return $return;
		}
	}

	function getListByOwner ($userId)
	{
		if($_SESSION['level']>=5)
		{
			$query = "SELECT d.id, d.name, count(r.id) AS records, fullname, u.id AS userId
			FROM domains d, records r, zones z, users u
			WHERE d.id=r.domain_id AND
			d.id = z.domain_id AND
			z.owner = u.id AND
			z.owner = '".$userId."'
			GROUP BY r.domain_id
			ORDER BY name;";

			$query = $this->database->query_slave($query) or die ($this->database->error());

			if($this->database->num_rows($query)==0)
			{
				return '';
			}else
			{
				while($record=$this->database->fetch_array($query))
				{
					$return[] = $record;
				}

				return $return;
			}
		}
	}

	function getAllOwners ()
	{
		$query = "SELECT id, fullname, level FROM users ORDER BY fullname";

		$query = $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			throw new Exception("No records found");
		}else
		{
			while($record=$this->database->fetch_array($query))
			{
				$return[] = $record;
			}

			return $return;
		}
	}

	function transferDomain ($domainId, $owner)
	{
		if($_SESSION['level']<5)
		{
			throw new Exception("No rights");
			return false;
		}

		$query = "UPDATE zones SET owner='".$this->database->escape_string($owner)."' WHERE domain_id='".$this->database->escape_string($domainId)."'";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception($this->database->error());
			return false;
		}
	}

	/* **************************************** */

	function addRecord ($domainId, $name, $type, $content, $ttl, $prio, $changeDate)
	{
		$query = "INSERT INTO `records` ( `id` , `domain_id` , `name` , `type` , `content` , `ttl` , `prio` , `change_date` ) VALUES
		( '', '".$this->database->escape_string($domainId)."', '".$this->database->escape_string(trim($name))."', '".$this->database->escape_string($type)."',
		'".$this->database->escape_string($content)."', '".$this->database->escape_string($ttl)."', '".$this->database->escape_string($prio)."', '".$this->database->escape_string($changeDate)."');";

		if($this->database->query_master($query))
		{
			// UPDATE THE SOA SERIAL
			$this->updateSoaSerial($domainId);

			return mysql_insert_id();
		}else
		{
			throw new Exception($this->database->error());
		}
	}

	function updateRecord ($orgRecordId, $recordId, $domainId, $name, $type, $content, $ttl, $prio, $changeDate, $updateSerial = true)
	{
		$query = "UPDATE `records` SET
		`id` = '".$this->database->escape_string($recordId)."', `domain_id` = '".$this->database->escape_string($domainId)."',
		`name` = '".$this->database->escape_string($name)."', `type` = '".$this->database->escape_string($type)."',
		`content` = '".$this->database->escape_string($content)."', `ttl` = '".$this->database->escape_string($ttl)."',
		`prio` = '".$this->database->escape_string($prio)."', `change_date` = '".$this->database->escape_string($changeDate)."'
		WHERE `id` = '".$this->database->escape_string($orgRecordId)."' LIMIT 1;";

		if($this->database->query_master($query))
		{
			if($updateSerial)
			{
				// UPDATE THE SOA SERIAL
				$this->updateSoaSerial($domainId);
			}

			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeRecord ($recordId, $domainId)
	{
		$query = "DELETE records FROM records, zones WHERE records.domain_id = zones.domain_id AND";

		if($_SESSION['level']<5)
		{
			$query .= " zones.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " records.id='".$this->database->escape_string($recordId)."'";

		if($this->database->query_master($query))
		{
			// UPDATE THE SOA SERIAL
			$this->updateSoaSerial($domainId);

			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function getAllRecords ($domainId)
	{
		$query = "SELECT * FROM zones z, records r
		WHERE r.domain_id = z.domain_id AND";

		if($_SESSION['level']<5)
		{
			$query .= " z.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " r.domain_id = '".$this->database->escape_string($domainId)."'
		ORDER BY r.type DESC, r.prio ASC, r.name ASC";

		$query = $this->database->query_slave($query) or die ($this->database->error());

		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			while($record=$this->database->fetch_array($query))
			{
				$return[] = $record;
			}

			return $return;
		}
	}

	function removeAllRecords ($domainId)
	{
		$query = "DELETE records FROM records, zones
		WHERE records.domain_id = zones.domain_id AND";

		if($_SESSION['level']<5)
		{
			$query .= " zones.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " records.domain_id='".$this->database->escape_string($domainId)."';";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function createNewSoaSerial ()
	{
		return date("Ymd").'00';
	}

	function updateSoaSerial ($domainId)
	{
		$query 		= "SELECT content FROM records WHERE domain_id='".$this->database->escape_string($domainId)."' AND type='SOA'";
		$query 		= $this->database->query_slave($query) or die ($this->database->error());
		$record		= $this->database->fetch_array($query);
		$soa		= explode(" ", $record['content']);

		if(substr($soa[2], 0, 8) != date("Ymd")) // IF THE SOA ISN'T OF TODAY THEN CREATE A NEW SOA
		{
			$soa[2] = $this->createNewSoaSerial();
		}else // SOA + 1
		{
			$soa[2]++;
		}

		return $this->setSoaSerial ($domainId, $soa[0], $soa[1], $soa[2]);
	}

	function setSoaSerial ($domainId, $ns0, $hostmaster, $serial)
	{
		$query		= "UPDATE records SET content='".$this->database->escape_string($ns0." ".$hostmaster." ".$serial)."' WHERE domain_id='".$this->database->escape_string($domainId)."' AND type='SOA'";

		if($this->database->query_master($query))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	/* **************************************** */
}
?>
