<?php
class manager
{
	public $database;

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
		if (!$username) throw new Exception("username is required");
		if (strlen($password) < 8) throw new Exception("password is required to be minimum 8 characters");
		if ($active != 0 && $active != 1) throw new Exception("active must be 0 or 1");
		if ($level < 1 || $level > 10) throw new Exception("level must be between 1 and 10");

		return $this->database->createModel('users', [
			'username' => $username,
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'fullname' => $fullname, 
			'email' => $email, 
			'description' => $description, 
			'level' => $level, 
			'active' => $active, 
			'maxdomains' => $maxdomains
		]);
	}

	function getUser ($userId)
	{
		global $u2f;
		$query = "SELECT * FROM users WHERE id = ?";
		$userdata = $this->database->fetchRow($query, [ $userId ]) or die ($this->database->error());

		if(!$userdata)
			throw new Exception("User not found");

		$u2fdata = array();
		if ($userdata['u2fdata']) $u2fdata = json_decode($userdata['u2fdata']);
		if (!is_array($u2fdata)) $u2fdata = array();
		
		list($req,$sigs) = $u2f->getRegisterData($u2fdata);
		$_SESSION['regReq'] = json_encode($req);
		$userdata['u2f_req'] = $req;
		$userdata['u2f_sigs'] = $sigs;
		
		return $userdata;
	}

	function updateUser ($orgUserId, $userId, $username, $password, $fullname, $email, $description, $level, $active, $maxdomains, $u2fdata)
	{
		global $u2f;
		$u2fdata = json_decode($u2fdata);
		foreach($u2fdata as &$d) {
			if (!$d->keyHandle) {
				$d = $u2f->doRegister(json_decode($_SESSION['regReq']), $d);
			}
		}
		$u2fdata = json_encode($u2fdata);
		
		$updateSet = [ 'id' => $userId, 'username' => $username, 'fullname' => $fullname, 'email' => $email, 'description' => $description, 'u2fdata' => $u2fdata ];
		if($_SESSION['level']>5)
		{
			$updateSet += ['level' => $level, 'active' => $active, 'maxdomains' => $maxdomains];
		}

		if($password!="")
		{
			$updateSet['password'] = password_hash($password, PASSWORD_DEFAULT);
		}

		
		if($_SESSION['level']<5 && $_SESSION['userId']!=$orgUserId || $_SESSION['level']>=5)
		{
			if($this->database->updateModel('users', [ 'id' => $orgUserId ], $updateSet))
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
			$this->database->deleteModel('users', [ 'id'=>$userId ]);
			return true;
		}
	}

	function removeUserData ($userId)
	{
		if($_SESSION['level']>=5)
		{

			$query = "DELETE FROM zones, domains, records USING zones, domains, records
			WHERE zones.domain_id = domains.id AND
			zones.domain_id = records.domain_id AND
			zones.owner = ?;";

			$this->database->queryMaster($query, [ $userId ]);
			return true;
		}
	}

	/* **************************************** */

	function addZone ($domainId, $ownerUserId, $comment)
	{
		return $this->database->createModel('zones', [
			'domain_id' => $domainId,
			'owner' => $ownerUserId,
			'comment' => $comment
		]);
	}

	function editZone ($domainId, $newOwnerUserId)
	{
		$idVals = ['domain_id' => $domainId];
		if($_SESSION['level']<5)
		{
			$idVals['owner'] = $_SESSION['userId'];
		}

		if($this->database->updateModel('zones', $idVals, [ 'owner' => $newOwnerUserId ]))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeZone ($zoneId)
	{
		$idVals = ['id' => $zoneId];
		if($_SESSION['level']<5)
		{
			$idVals['owner'] = $_SESSION['userId'];
		}

		if($this->database->deleteModel('zones', $idVals))
		{
			return true;
		}else
		{
			throw new Exception ($this->database->error());
		}
	}

	function removeZoneByDomainId ($domainId)
	{
		$idVals = ['domain_id' => $domainId];
		if($_SESSION['level']<5)
		{
			$idVals['owner'] = $_SESSION['userId'];
		}

		if($this->database->deleteModel('zones', $idVals, false))
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
		$query = "SELECT * FROM domains WHERE id=?";

		return $this->database->fetchRow($query, [ $domainId ]);
	}

	function addDomain ($name, $master, $lastCheck, $type, $notifiedSerial, $account)
	{
		if($_SESSION['level'] == 1)
		{
			if(!$this->canAddDomainCheckMax($_SESSION['userId']))
			{
				throw new Exception("Max domain setting reached. Please ask your host to update your max domains setting.");
			}
		}

		return $this->database->createModel('domains', [
			"name" => trim($name),
			"master" => $master,
			"last_check" => $lastCheck,
			"type" => $type,
			"notified_serial" => $notifiedSerial,
			"account" => $account
		]);
	}

	function updateDomain ($orgDomainId, $domainId, $name, $master, $lastCheck, $type, $notifiedSerial, $account)
	{
		$this->database->updateModel('domains', [ 'id' => $orgDomainId ], [
			"id" => $domainId, "name" => $name,
			"master" => $master, "last_check" => $lastCheck,
			"type" => $type, "notified_serial" => $notifiedSerial,
			"account" => $account
		]);
	}

	function removeDomain ($domainId)
	{
		$this->database->deleteModel('domains', [ 'id' => $domainId ]);
	}

	function canAddDomainCheckMax ($userId)
	{
		$query = "SELECT count(owner) AS current FROM zones WHERE owner = ?";
		$record	= $this->database->fetchRow($query, [ $userId ]) or die ($this->database->error());
		
		$user = $this->getUser($userId);

		if($record['current'] < $user['maxdomains'] || $user['maxdomains'] == 0)
		{
			return true;
		}

		return false;
	}

	function searchDomains ($q)
	{
		$return = array();
		$queryArgs = [];
		$query	= "SELECT d.id, d.name, count(r.id) AS records, fullname, u.id AS userId
		FROM domains d, records r, zones z, users u
		WHERE d.id=r.domain_id AND
		d.id = z.domain_id AND
		z.owner = u.id AND";

		if($_SESSION['level']==1)
		{
			$query .= " z.owner = ? AND";
			$queryArgs []= $_SESSION['userId'];
		}

		$query .= " d.name LIKE ?
		GROUP BY r.domain_id
		ORDER BY name";
		$queryArgs []= "%$q%";
		return $this->database->fetchAll($query, $queryArgs);
	}

	function getListByLetter ($letter)
	{
		$queryArgs = [ "^$letter" ];
		$query = "SELECT d.id, d.name, d.name REGEXP ? AS regex, count(r.id) AS records, fullname, u.id AS userId
		FROM domains d, records r, zones z, users u
		WHERE d.id=r.domain_id AND
		d.id = z.domain_id AND";

		if($_SESSION['level']==1)
		{
			$query .= " z.owner = ? AND";
			$queryArgs []= $_SESSION['userId'];
		}

		$query .= " z.owner = u.id
		GROUP BY r.domain_id
		HAVING regex = 1
		ORDER BY name;";
		return $this->database->fetchAll($query, $queryArgs);
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
			z.owner = ?
			GROUP BY r.domain_id
			ORDER BY name;";

			return $this->database->fetchAll($query, [ $userId ]);
		} else {
			return $this->searchDomains('');
		}
	}

	function getAllOwners ()
	{
		$query = "SELECT id, fullname, level FROM users ORDER BY fullname";

		return $this->database->fetchAll($query, []);
	}

	function transferDomain ($domainId, $owner)
	{
		if($_SESSION['level']<5)
		{
			throw new Exception("No rights");
			return false;
		}

		$this->database->updateModel('zones', ['domain_id'=>$domainId], ['owner'=>$owner ]);
	}

	/* **************************************** */

	function addRecord ($domainId, $name, $type, $content, $ttl, $prio, $changeDate)
	{
		$id = $this->database->createModel('records', [
			"domain_id" => $domainId,
			"name" => trim($name),
			"type" => $type,
			"content" => $content,
			"ttl" => $ttl,
			"prio" => $prio,
			"change_date" => $changeDate,
		]);
		
		// UPDATE THE SOA SERIAL
		$this->updateSoaSerial($domainId);

		return $id;
	}

	function updateRecord ($orgRecordId, $recordId, $domainId, $name, $type, $content, $ttl, $prio, $changeDate, $updateSerial = true)
	{
		$this->database->updateModel('records', [ "id" => $orgRecordId ], [
			"id" => $recordId, "domain_id" => $domainId,
			"name" => $name, "type" => $type,
			"content" => $content, "ttl" => $ttl,
			"prio" => $prio, "change_date" => $changeDate
		], false);  //don't show error if no change was made
		
		if($updateSerial)
		{
			// UPDATE THE SOA SERIAL
			$this->updateSoaSerial($domainId);
		}

		return true;
		
	}

	function removeRecord ($recordId, $domainId)
	{
		$query = "DELETE records FROM records, zones WHERE records.domain_id = zones.domain_id AND";
		$queryArgs = [];
		if($_SESSION['level']<5)
		{
			$query .= " zones.owner = ? AND";
			$queryArgs []= $_SESSION['userId'];
		}

		$query .= " records.id=?";
		$queryArgs []= $recordId;
		if($this->database->queryMaster($query, $queryArgs))
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
		$query = "SELECT *, CONCAT(r.id,'.',r.prio,'.',r.name,'.',9-find_in_set(r.type, 'MX,NS,SOA')) AS `_sort_key` FROM zones z, records r
		WHERE r.domain_id = z.domain_id AND";

		if($_SESSION['level']<5)
		{
			$query .= " z.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " r.domain_id = ?";

		$stmt = $this->database->querySlave($query, [ $domainId ]) or die ($this->database->error());

		$return = array();
		while($record=$stmt->fetch(PDO::FETCH_ASSOC))
		{
			$sortkey = implode('.', array_reverse(explode('.', $record['_sort_key'])));
			$return[$sortkey] = $record;
		}
		ksort($return);
		return array_values($return);
	}

	function removeAllRecords ($domainId)
	{
		$query = "DELETE records FROM records, zones
		WHERE records.domain_id = zones.domain_id AND";

		if($_SESSION['level']<5)
		{
			$query .= " zones.owner = '".$_SESSION['userId']."' AND";
		}

		$query .= " records.domain_id=?;";

		$this->database->queryMaster($query, [ $domainId ]);
		return true;
	}

	function createNewSoaSerial ()
	{
		return date("Ymd").'00';
	}

	function updateSoaSerial ($domainId)
	{
		$query 		= "SELECT content FROM records WHERE domain_id=? AND type='SOA'";
		$record 	= $this->database->fetchRow($query, [ $domainId ]) or die ($this->database->error());
		$soa		= explode(" ", $record['content']);

		if(substr($soa[2], 0, 8) != date("Ymd")) // IF THE SOA ISN'T OF TODAY THEN CREATE A NEW SOA
		{
			$soa[2] = $this->createNewSoaSerial();
		}else // SOA + 1
		{
			$soa[2]++;
		}

		return $this->setSoaSerial ($domainId, $soa[0], $soa[1], $soa[2], $soa[3], $soa[4], $soa[5], $soa[6]);
	}

	function setSoaSerial ($domainId, $ns0, $hostmaster, $serial, $refresh, $retry, $expire, $ttl)
	{
		if(!$refresh) $refresh = 3600;
		if(!$retry) $retry = 1800;
		if(!$expire) $expire = 3600000;
		if(!$ttl) $ttl = 172800;
		
		return $this->database->updateModel('records', [ 'domain_id' => $domainId, 'type' => 'SOA' ],
										[ 'content' => "$ns0 $hostmaster $serial $refresh $retry $expire $ttl" ]);
	}

	/* **************************************** */
}
