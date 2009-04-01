function resultError (request)
{
	alert('Error ' + request.status + ' -- ' + request.statusText + ' -- ' + request.responseText);
}

function dummy ()
{
	return true;
}

function succesFailed (request)
{
	if (request.readyState==4)
	{
		// JAVASCRIPT DEBUG INFORMATION
		//document.getElementById("query").innerHTML = request.responseText;
		
		var jsonData = eval('('+request.responseText+')');

		if(jsonData.status=="success")
		{
			alert(jsonData.text);
		}else
		{
			alert("Failed: "+jsonData.text);
		}
		
		if(jsonData.reload=="yes")
		{
			window.location.reload();
		}
	}
}

function displayList (id, name, records, fullname, userId)
{
	output  = '<tr><td>[ <a href="javascript:deleteZone(\''+id+'\')">delete zone</a> ]</td>';
	output += '<td><a href="javascript:editDomain('+id+');">'+name+'</a></td>';
	output += '<td>'+records+'</td>';
	output += '<td><a href="javascript:ownersList(\''+userId+'\');">'+fullname+'</a></td></tr>';
	
	return output;
}

function list (letter)
{
	new Ajax.Request(baseurl+"?p=letterlist&letter="+letter, 
	{
		method:"get",
		asynchronous:true,
		onSuccess:showList,
		onFailure:resultError
	});
}

function showList (request)
{
	if (request.readyState==4) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = eval('('+request.responseText+')');
		var output = '<table width="800">';
		
		if(request.responseText.length==2)
		{
			output += '<tr><td>No results found</td></tr>';
		}
		
		for(i=0; i<jsonData.length; i++)
		{
			output += displayList (jsonData[i].id, jsonData[i].name, jsonData[i].records, jsonData[i].fullname, jsonData[i].userId);
		}
		
		output += '</table>';
		
		document.getElementById("body").innerHTML = output;
	}
}

function liveSearchStart()
{
	new Ajax.Request(baseurl+"?p=livesearch&q="+document.getElementById('livesearch').value, 
	{
		method:"get",
		asynchronous:true,
		onSuccess:liveSearchResults,
		onFailure:resultError
	});
}

function liveSearchResults (request) 
{
	if (request.readyState==4) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = eval('('+request.responseText+')');
		var output = '<table width="800">';
		
		if(request.responseText.length==2)
		{
			output += '<tr><td>No results found</td></tr>';
		}
		
		for(i=0; i<jsonData.length; i++)
		{
			output += displayList (jsonData[i].id, jsonData[i].name, jsonData[i].records, jsonData[i].fullname, jsonData[i].userId);
		}
		
		output += '</table>';
		
		document.getElementById("body").innerHTML = output;
	}
}

function ownersList (userId)
{
	new Ajax.Request(baseurl+"?p=ownerslist&userId="+userId, 
	{
		method:"get",
		asynchronous:true,
		onSuccess:showOwnersList,
		onFailure:resultError
	});
}

function showOwnersList (request)
{
	if (request.readyState==4) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = eval('('+request.responseText+')');
		var output = '<table width="800">';
		
		if(request.responseText.length==2)
		{
			output += '<tr><td>No results found</td></tr>';
		}
		
		for(i=0; i<jsonData.length; i++)
		{
			output += displayList (jsonData[i].id, jsonData[i].name, jsonData[i].records, jsonData[i].fullname, jsonData[i].userId);
		}
		
		output += '</table>';
		
		document.getElementById("body").innerHTML = output;
	}
}

function deleteZone (domainId)
{
	if(confirm("Are you sure you want to delete this domain?"))
	{
		new Ajax.Request(baseurl+"?p=deleteZone&domainId="+escape(domainId), 
		{
			method:"get",
			asynchronous:true,
			onSuccess:succesFailed,
			onFailure:resultError
		});
	}
}

function editDomain (domainId)
{
	new Ajax.Request(baseurl+"?p=getDomainInfo&domainId="+escape(domainId), 
	{
		method:"get",
		asynchronous:true,
		onSuccess:editDomainWindow,
		onFailure:resultError
	});
}

function editDomainWindow (request)
{	
	if (request.readyState==4)
	{
		if(request.responseText=='failed')
		{
			alert("The action you performed failed.");
		}else
		{
			var jsonData = eval('('+request.responseText+')');
			
			new Ajax.Request(baseurl+"?p=getOwners",
        		{
                		method:"get",
                		asynchronous:true,
                		onSuccess:changeOwnersSelect,
                		onFailure:resultError
        		});


			var result  = '<p><table>';
			result += '  <tr>';
			result += '	<td><h1>Edit domain :: '+jsonData.domain.name+' ('+jsonData.records.length+')</h1></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	<td><table>';
			result += '	<tr><td><b>name</b></td><td><b>type</b></td><td><b>content</b></td><td><b>prio</b></td><td><b>ttl</b></td><td>&nbsp;</td><td>&nbsp;</td></tr>';
			
			for(i=0; i<jsonData.records.length; i++)
			{
				var r = jsonData.records[i];
				result += '<tr>';
				result += '<td><input type="text" value="'+r.name+'" id="name['+i+']"><input type="hidden" value="'+r.id+'" id="id['+i+']"></td>';
				result += '<td><input type="text" size="4" value="'+r.type+'" id="type['+i+']"></td>';
				result += '<td><input type="text" size="50" value="'+r.content+'" id="content['+i+']"></td>';
				result += '<td><input type="text" size="2" value="'+r.prio+'" id="prio['+i+']"></td>';
				result += '<td><input type="text" size="4" value="'+r.ttl+'" id="ttl['+i+']"></td>';
				result += '<td><input type="button" onclick="removeRecord('+r.id+', '+jsonData.domain.id+');setTimeout(\'editDomain('+jsonData.domain.id+');\', 100);" value="delete record" id="delete['+i+']"></td>';
				result += '<td><input type="button" onclick="javascript:saveRecord('+jsonData.domain.id+', document.getElementById(\'id['+i+']\').value, ';
				result += 'document.getElementById(\'name['+i+']\').value, document.getElementById(\'type['+i+']\').value, ';
				result += 'document.getElementById(\'content['+i+']\').value, document.getElementById(\'prio['+i+']\').value, ';
				result += 'document.getElementById(\'ttl['+i+']\').value); setTimeout(\'editDomain('+jsonData.domain.id+');\', 100);" id="save['+i+']" value="save"></td>';
				result += '</tr>';
			}
			
			result += '	</table></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	<td><h1>Add a record</h1></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	   <td><table>';
			result += '	   <tr><td><b>name</b></td><td><b>type</b></td><td><b>content</b></td><td><b>prio</b></td><td><b>ttl</b></td><td>&nbsp;</td></tr>';
			result += '    <tr><td><input type="text" value="'+r.name+'" id="new[name]" /></td>';
			result += '    <td><select id="new[type]"><option selected="selected" value="A">A</option>';
			result += '    <option value="AAAA">AAAA</option><option value="CNAME">CNAME</option>';
			result += '    <option value="HINFO">HINFO</option><option value="MX">MX</option>';
			result += '    <option value="NAPTR">NAPTR</option><option value="NS">NS</option>';
			result += '    <option value="PTR">PTR</option><option value="SOA">SOA</option>';
			result += '    <option value="TXT">TXT</option><option value="URL">URL</option>';
			result += '    <option value="SRV">SRV</option><option value="MBOXFW">MBOXFW</option></select></td>';
			result += '	   <td><input type="content" size="50" value="" id="new[content]" /></td>';
			result += '	   <td><input type="prio" size="2" value="0" id="new[prio]" /"></td>';
			result += '	   <td><input type="ttl" size="4" value="3600" id="new[ttl]" /></td>';
			result += '	   <td><input type="button" onclick="newRecord('+jsonData.domain.id+', document.getElementById(\'new[name]\').value, ';
			result += 'document.getElementById(\'new[type]\').value, document.getElementById(\'new[content]\').value, ';
			result += 'document.getElementById(\'new[prio]\').value, document.getElementById(\'new[ttl]\').value); setTimeout(\'editDomain('+jsonData.domain.id+');\', 100);" id="new[save]" value="save" />';
			result += '	</tr></table></td>';
			result += '  </tr>';

			if(userlevel >= 5)
			{
				result += '  <tr>';
                        	result += '     <td><h1>Transfer domain</h1></td>';
                        	result += '  </tr>';
                        	result += '  <tr>';
                        	result += '    <td>Transfer domain to <select id="owner"></select>';
				result += ' <input type="button" onclick="transferDomain('+jsonData.domain.id+', document.getElementById(\'owner\').value);" value="transfer" /></td>';
                        	result += '  </tr>';
				result += '</table></p>';
			}
			
			document.getElementById('body').innerHTML = result;
		}
	}
}

function transferDomain (domainId, owner)
{
	new Ajax.Request(baseurl+"?p=transferDomain",
        {
                method:"post",
                postBody:"domainId="+escape(domainId)+"&owner="+escape(owner),
                asynchronous:true,
                onSuccess:succesFailed,
                onFailure:resultError
        });
}

function removeRecord (recordId, domainId)
{
	if(confirm("Are you sure you want to delete the record?"))
	{
		new Ajax.Request(baseurl+"?p=removeRecord&recordId="+escape(recordId)+"&domainId="+escape(domainId), 
		{
			method:"get",
			asynchronous:true,
			onSuccess:succesFailed,
			onFailure:resultError
		});
	}
}

function saveRecord(domainId, recordId, name, type, content, prio, ttl)
{
	new Ajax.Request(baseurl+"?p=saveRecord", 
	{
		method:"post",
		postBody:"domainId="+escape(domainId)+"&recordId="+escape(recordId)+"&name="+escape(name)+"&type="+escape(type)+"&content="+escape(content)+"&prio="+escape(prio)+"&ttl="+escape(ttl),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function newRecord (domainId, name, type, content, prio, ttl)
{
	new Ajax.Request(baseurl+"?p=newRecord", 
	{
		method:"post",
		postBody:"domainId="+escape(domainId)+"&name="+escape(name)+"&type="+escape(type)+"&content="+escape(content)+"&prio="+escape(prio)+"&ttl="+escape(ttl),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function newDomain ()
{
	new Ajax.Request(baseurl+"?p=getOwners", 
	{
		method:"get",
		asynchronous:true,
		onSuccess:changeOwnersSelect,
		onFailure:resultError
	});
	
	new Ajax.Request(baseurl+"?p=getTemplates", 
	{
		method:"get",
		asynchronous:true,
		onSuccess:changeTemplateSelect,
		onFailure:resultError
	});
	
	var result  = '<table>';
	result += '<tr>';
	result += '	 <td colspan="2"><h1>Add a domain</h1></td>';
	result += '</tr>';
	result += '<tr><td>Domain name:</td><td><input type="text" id="domain" /></td></tr>';
	result += '<tr><td>WEB IP:</td><td><input type="text" id="webIP" /></td></tr>';
	result += '<tr><td>Mail IP:</td><td><input type="text" id="mailIP" /></td></tr>';
	result += '<tr><td>Owner:</td><td><select id="owner"></select></td></tr>';
	result += '<tr><td>Template:</td><td><select id="template"></select></td></tr>';
	result += '<tr><td>&nbsp;</td><td><input type="button" id="add" value="Add domain" onClick="saveNewRecord(document.getElementById(\'domain\').value, ';
	result += 'document.getElementById(\'webIP\').value, document.getElementById(\'mailIP\').value, document.getElementById(\'owner\').value, ';
	result += 'document.getElementById(\'template\').value);" /></td></tr>';
	result += '</table>';
	
	document.getElementById('body').innerHTML = result;
}

function bulkNewDomain ()
{
	new Ajax.Request(baseurl+"?p=getOwners", 
	{
		method:"get",
		asynchronous:true,
		onSuccess:changeOwnersSelect,
		onFailure:resultError
	});
	
	new Ajax.Request(baseurl+"?p=getTemplates", 
	{
		method:"get",
		asynchronous:true,
		onSuccess:changeTemplateSelect,
		onFailure:resultError
	});
	
	var result  = '<table>';
	result += '<tr>';
	result += '	 <td colspan="2"><h1>Add multiple domains</h1></td>';
	result += '</tr>';
	result += '<tr><td>Domain names:<br />(one domain per line)</td><td><textarea id="domains" rows="10" cols="50"></textarea></td></tr>';
	result += '<tr><td>WEB IP:</td><td><input type="text" id="webIP" /></td></tr>';
	result += '<tr><td>Mail IP:</td><td><input type="text" id="mailIP" /></td></tr>';
	result += '<tr><td>Owner:</td><td><select id="owner"></select></td></tr>';
	result += '<tr><td>Template:</td><td><select id="template"></select></td></tr>';
	result += '<tr><td>&nbsp;</td><td><input type="button" id="add" value="Add domains" onClick="saveNewDomains(document.getElementById(\'domains\').value, ';
	result += 'document.getElementById(\'webIP\').value, document.getElementById(\'mailIP\').value, document.getElementById(\'owner\').value, ';
	result += 'document.getElementById(\'template\').value);" /></td></tr>';
	result += '</table>';
	
	document.getElementById('body').innerHTML = result;
}

function changeOwnersSelect (request)
{
	if (request.readyState==4)
	{
		var jsonData = eval('('+request.responseText+')');
		
		document.getElementById('owner').options.length=0;
		
		for(i=0; i<jsonData.length; i++)
		{
			if(userlevel<5)
			{
				if(myUserId==jsonData[i].id)
				{
					document.getElementById('owner').options[i]=new Option(jsonData[i].fullname, jsonData[i].id, false, true);
				}
			}else
			{
				document.getElementById('owner').options[i]=new Option(jsonData[i].fullname, jsonData[i].id, false, false);
			}
		}
	}
}

function changeTemplateSelect (request)
{
	if (request.readyState==4)
	{
		var jsonData = eval('('+request.responseText+')');
		
		document.getElementById('template').options.length=0;
		
		for(i=0; i<jsonData.length; i++)
		{
			document.getElementById('template').options[i]=new Option(jsonData[i], jsonData[i], false, false);
		}
	}
}

function saveNewRecord (domain, webIP, mailIP, owner, template)
{
	new Ajax.Request(baseurl+"?p=newDomain", 
	{
		method:"post",
		postBody:"domain="+escape(domain)+"&webIP="+escape(webIP)+"&mailIP="+escape(mailIP)+"&owner="+escape(owner)+"&template="+escape(template),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function saveNewDomains (domains, webIP, mailIP, owner, template)
{
	new Ajax.Request(baseurl+"?p=newDomains", 
	{
		method:"post",
		postBody:"domains="+escape(domains)+"&webIP="+escape(webIP)+"&mailIP="+escape(mailIP)+"&owner="+escape(owner)+"&template="+escape(template),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function userAdmin ()
{
	new Ajax.Request(baseurl+"?p=getOwners", 
	{
		method:"get",
		asynchronous:true,
		onSuccess:showUserAdmin,
		onFailure:resultError
	});
}

function showUserAdmin (request)
{
	if (request.readyState==4)
	{
		var jsonData = eval('('+request.responseText+')');
		
		var result = '<table width="800">';
		
		for(i=0; i<jsonData.length; i++)
		{
			if(!(userlevel<5 && jsonData[i].id != myUserId))
			{
				result += '<tr>';
				result += '  <td>[ <a onclick="deleteUser('+jsonData[i].id+');setTimeout(\'userAdmin();\', 2500);">delete user</a> ]</td>';
				result += '  <td><a href="javascript:editUser('+jsonData[i].id+');">'+jsonData[i].fullname+'</a></td>';
				result += '  <td>'+jsonData[i].level+'</td>';
				result += '</tr>';
			}
		}
		
		result += '<tr><td colspan="3">&nbsp;</td></tr>';
		result += '<tr><td colspan="3"><h1>Add a user</h1></td></tr>';
		result += '<tr><td>Username</td><td colspan="2"><input type="text" id="username"></td></tr>';
		result += '<tr><td>Password</td><td colspan="2"><input type="text" id="password"></td></tr>';
		result += '<tr><td>Password check</td><td colspan="2"><input type="text" id="passwordcheck"></td></tr>';
		result += '<tr><td>Full name</td><td colspan="2"><input type="text" id="fullname"></td></tr>';
		result += '<tr><td>E-mail</td><td colspan="2"><input type="text" id="email"></td></tr>';
		result += '<tr><td>Description</td><td colspan="2"><input type="text" id="description"></td></tr>';
		result += '<tr><td>Level</td><td colspan="2"><input type="text" id="level"></td></tr>';
		result += '<tr><td>Active</td><td colspan="2"><input type="text" id="active"></td></tr>';
		result += '<tr><td colspan="3"><input type="button" id="save" value="Add user" onclick="addUser(';
		result += ' document.getElementById(\'username\').value, document.getElementById(\'password\').value, document.getElementById(\'passwordcheck\').value,';
		result += ' document.getElementById(\'fullname\').value, document.getElementById(\'email\').value, document.getElementById(\'description\').value,';
		result += ' document.getElementById(\'level\').value, document.getElementById(\'active\').value);setTimeout(\'userAdmin();\', 2500);"></td></tr>';
		result += '</table>';
		
		document.getElementById("body").innerHTML = result;
	}
}

function deleteUser (userId)
{
	if(confirm("Are you sure you want to delete this user, and all it's data?"))
	{
		new Ajax.Request(baseurl+"?p=deleteUser",
		{
			method:"post",
			postBody:"userId="+userId,
			asynchronous:true,
			onSuccess:succesFailed,
			onFailure:resultError
		});
	}
}

function editUser (userId)
{
	new Ajax.Request(baseurl+"?p=getUser", 
	{
		method:"post",
		postBody:"userId="+userId,
		asynchronous:true,
		onSuccess:showEditUser,
		onFailure:resultError
	});
}

function showEditUser (request)
{
	if (request.readyState==4)
	{
		var jsonData = eval('('+request.responseText+')');
		
		var result = '<table width="800"><input type="hidden" id="userId" value="'+jsonData.id+'">';
		result += '<tr><td>Username</td><td><input type="text" id="username" value="'+jsonData.username+'"></td></tr>';
		result += '<tr><td>Password</td><td><input type="password" id="password" value=""></td></tr>';
		result += '<tr><td>Full name</td><td><input type="text" id="fullname" value="'+jsonData.fullname+'"></td></tr>';
		result += '<tr><td>E-mail</td><td><input type="text" id="email" value="'+jsonData.email+'"></td></tr>';
		result += '<tr><td>Description</td><td><textarea id="description">'+jsonData.description+'</textarea></td></tr>';
		result += '<tr><td>Level</td><td><input type="text" id="level" value="'+jsonData.level+'"></td></tr>';
		result += '<tr><td>Active</td><td><input type="text" id="active" value="'+jsonData.active+'"></td></tr>';
		result += '<tr><td><input type="button" name="save" value="Opslaan" onclick="javascript:saveUser(';
		result += 'document.getElementById(\'userId\').value, document.getElementById(\'username\').value, document.getElementById(\'password\').value,';
		result += ' document.getElementById(\'fullname\').value, document.getElementById(\'email\').value, document.getElementById(\'description\').value,';
		result += ' document.getElementById(\'level\').value, document.getElementById(\'active\').value);"></td><td></td></tr>';
		result += '</table>';
		
		document.getElementById("body").innerHTML = result;
	}
}

function addUser (username, password, passwordcheck, fullname, email, description, level, active)
{
	new Ajax.Request(baseurl+"?p=addUser", 
	{
		method:"post",
		postBody:"username="+username+"&password="+password+"&passwordcheck="+passwordcheck+"&fullname="+fullname+"&email="+email+"&description="+description+"&level="+level+"&active="+active,
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function saveUser (userId, username, password, fullname, email, description, level, active)
{
	new Ajax.Request(baseurl+"?p=editUser", 
	{
		method:"post",
		postBody:"userId="+userId+"&username="+username+"&password="+password+"&fullname="+fullname+"&email="+email+"&description="+description+"&level="+level+"&active="+active,
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function loginForm ()
{
	var result  = '<p><form method="post" action="index.php"><table>';
	result += '  <tr>';
	result += '	<td rowspan="4" width="70"><img src="images/agent.png" alt="Please login" /></td>';
	result += '	<td colspan="3"><b>Login<span id="infoHead"></span></b></td>';
	result += '  </tr>';
	result += '  <tr>';
	result += '	<td>Username:</td>';
	result += '	<td><input type="text" id="usernamefield" tabindex="1" /></td>';
	result += '	<td rowspan="2"><img src="images/forward.png" id="loginstatus" alt="login status" /></td>';
	result += '  </tr>';
	result += '  <tr>';
	result += '	<td>Password:</td>';
	result += '	<td><input type="password" id="passwordfield" tabindex="2" /></td>';
	result += '  </tr>';
	result += '  <tr>';
	result += '	<td colspan="2"><input type="button" value="login" tabindex="3" id="loginBtn" onclick=\'login(document.getElementById("usernamefield").value, document.getElementById("passwordfield").value);\' /></td>';
	result += '  </tr>';
	result += '</table></form></p>';
	document.getElementById('login').innerHTML = result;
}

function login (username, password)
{
	new Ajax.Request(baseurl+"?p=doLogin", 
	{
		method:"post",
		postBody:"username="+escape(username)+"&password="+escape(password),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}
