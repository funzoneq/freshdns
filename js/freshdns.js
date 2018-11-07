var timeoutInMilisec = 100;
var lastAddedName = "", lastAddedType = "", lastAddedContent="";
var currentEditedDomain = null;
var currentHash = null;

function init() {
	if (!myUserId) return;
	$("li[data-navigate-list]").click(onNavigateListClicked);
	onNavigateHash(location.hash);
	window.addEventListener('hashchange', function(e) {
		console.log(location.hash , currentHash);
		if (location.hash == currentHash) return;
		onNavigateHash(location.hash);
	});
}
$(document).ready(init);

function onNavigateListClicked(e) {
	var letter = this.getAttribute("data-navigate-list");
	list(letter);
	return false;
}

function onNavigateHash(hash) {
	var parts = hash.split(/=/);
	switch(parts[0]) {
		case '#domain': editDomain(parseInt(parts[1])); break;
		case '#list': list(parts[1]); break;
		case '#admin': userAdmin(); break;
		case '#user': editUser(parts[1]); break;
	}
}
function updateHash(hash) {
	currentHash = hash;
	location.hash = hash;
}


function resetActive() {
		$("li.active").removeClass("active");
}
function resultError (request) {
		message('danger', 'Error ' + request.status + ' -- ' + request.statusText + ' -- ' + request.responseText);
}

Ajax.Responders.register({
	onException: function(req, ex) {
		console.warn("Unhandled Exception in AJAX handler",ex);
		message('danger', 'Unhandled Exception: '+ex);
	}
});

function resultDebug (request)
{
	document.getElementById("debug").style.display = 'inline';
	document.getElementById("debug").innerHTML = request.responseText;
}

function dummy ()
{
	return true;
}

function message(style,text) {
	var msg = document.getElementById("message");
	msg.innerHTML = "<div class='alert alert-"+style+"'>" + text + "</div>";
	msg.onclick = function() { msg.innerHTML = ""; }
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
			message("success", jsonData.text);
		}else
		{
			message("danger", "Failed: "+jsonData.text);
		}
		
		if(jsonData.reload=="yes")
		{
			window.location.reload();
		}

		if (jsonData.u2f_challenge) {
			doU2fSignature(jsonData.u2f_challenge);
		}
	}
}

function displayList (id, name, records, fullname, userId)
{
	output  = '<tr><td> <a href="javascript:deleteZone(\''+id+'\')"><span class="glyphicon glyphicon-trash"></span></a> </td>';
	output += '<td><a href="javascript:editDomain('+id+');">'+name+'</a></td>';
	output += '<td>'+records+'</td>';
	output += '<td><a href="javascript:ownersList(\''+userId+'\');">'+fullname+'</a></td></tr>';
	
	return output;
}

function list (letter)
{
	resetActive();
	$("li[data-navigate-list='"+letter+"']").addClass("active");
	new Ajax.Request(baseurl+"?p=letterlist&letter="+letter, 
	{
		method:"get",
		asynchronous:true,
		onSuccess:showList,
		onFailure:resultError
	});
	updateHash('#list=' + letter);
}

function showList (request)
{
	if (request.readyState==4) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = eval('('+request.responseText+')');
		var output = '<h3>List</h3><table class="table table-condensed table-striped">';
		
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
		var output = '<table class="table table-condensed table-striped">';
		
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
		var output = '<table class="table table-condensed table-striped">';
		
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
		new Ajax.Request(baseurl+"?p=deleteZone",
		{
			method:"post",
			postBody:"domainId="+encodeURIComponent(domainId),
			asynchronous:true,
			onSuccess:succesFailed,
			onFailure:resultError
		});
	}
}

function editDomain (domainId)
{
	updateHash('#domain=' + domainId);
	new Ajax.Request(baseurl+"?p=getDomainInfo&domainId="+encodeURIComponent(domainId), 
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
				message("danger", "The action you performed failed.");
		}else
		{
			var jsonData = currentEditedDomain = JSON.parse(request.responseText);
			
			new Ajax.Request(baseurl+"?p=getOwners",
				{
					method:"get",
					asynchronous:true,
					onSuccess:changeOwnersSelect,
					onFailure:resultError
				});

			var name = jsonData.domain.name;
			var result  = '<p><table>';
			result += '  <tr>';
			result += '	<td><h3>Edit domain :: <b>'+name+'</b> ('+jsonData.records.length+')</h3></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	<td><form name="editDomain" action="javascript:saveAllRecords(document.editDomain);">';
			result += '	<input type="hidden" id="domainId" value="'+jsonData.domain.id+'" /><table id="recordsTable">';
			result += '	<tr><td><b>name</b></td><td><b>type</b></td><td><b>content</b></td><td><b>prio</b></td><td><b>ttl</b></td><td>&nbsp;</td><td>&nbsp;</td></tr>';
			
			for(i=0; i<jsonData.records.length; i++)
			{
				var r = jsonData.records[i];
				result += '<tr>';
				result += '<td><input type="text" value="'+r.name.replace(name, '')+'" id="name['+i+']"><input type="hidden" value="'+r.id+'" id="id['+i+']"></td>';
				result += '<td><input type="text" size="6" class="type" value="'+r.type+'" id="type['+i+']"></td>';
				result += '<td><input type="text" size="50" value="'+r.content.replace(/"/g, '&quot;')+'" id="content['+i+']"></td>';
				result += '<td><input type="text" size="2" class="num" value="'+r.prio+'" id="prio['+i+']"></td>';
				result += '<td><input type="text" size="4" class="num" value="'+r.ttl+'" id="ttl['+i+']"></td>';
				result += '<td><input type="button" onclick="removeRecord('+r.id+', '+jsonData.domain.id+');setTimeout(\'editDomain('+jsonData.domain.id+');\', '+timeoutInMilisec+');" value="delete" id="delete['+i+']"></td>';
				result += '<td><input type="button" onclick="javascript:saveRecord('+jsonData.domain.id+', document.getElementById(\'id['+i+']\').value, ';
				result += 'document.getElementById(\'name['+i+']\').value, document.getElementById(\'type['+i+']\').value, ';
				result += 'document.getElementById(\'content['+i+']\').value, document.getElementById(\'prio['+i+']\').value, ';
				result += 'document.getElementById(\'ttl['+i+']\').value); setTimeout(\'editDomain('+jsonData.domain.id+');\', '+timeoutInMilisec+');" id="save['+i+']" value="save record"></td>';
				result += '</tr>';
			}
			
			result += '	<tr><td colspan="7"><input type="submit" value="save all changes"></td></tr></table></form></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	<td><h3>Add a record</h3></td>';
			result += '  </tr>';
			result += '  <tr>';
			result += '	   <td><table>';
			result += '	   <tr><td><b>name</b></td><td><b>type</b></td><td><b>content</b></td><td><b>prio</b></td><td><b>ttl</b></td><td>&nbsp;</td></tr>';
			result += '    <tr><td><input type="text" value="'+(lastAddedName?lastAddedName:r.name.replace(name,''))+'" id="new[name]" /></td>';
			result += '    <td><select id="new[type]"><option selected="selected" value="A">A</option>';
			result += '    <option value="AAAA">AAAA</option><option value="CNAME">CNAME</option>';
			result += '    <option value="HINFO">HINFO</option><option value="MX">MX</option>';
			result += '    <option value="NAPTR">NAPTR</option><option value="NS">NS</option>';
			result += '    <option value="PTR">PTR</option><option value="SOA">SOA</option>';
			result += '    <option value="TXT">TXT</option><option value="URL">URL</option>';
			result += '    <option value="SRV">SRV</option><option value="MBOXFW">MBOXFW</option></select></td>';
			result += '	   <td><input type="content" size="50" value="'+lastAddedContent+'" id="new[content]" /></td>';
			result += '	   <td><input type="prio" size="2" value="0" id="new[prio]" /"></td>';
			result += '	   <td><input type="ttl" size="4" value="3600" id="new[ttl]" /></td>';
			result += '	   <td><input type="button" onclick="newRecord('+jsonData.domain.id+', document.getElementById(\'new[name]\').value, ';
			result += 'document.getElementById(\'new[type]\').value, document.getElementById(\'new[content]\').value, ';
			result += 'document.getElementById(\'new[prio]\').value, document.getElementById(\'new[ttl]\').value); setTimeout(\'editDomain('+jsonData.domain.id+');\', '+timeoutInMilisec+');" id="new[save]" value="save" />';
			result += '	</tr></table></td>';
			result += '  </tr>';
			
			if(userlevel >= 5)
			{
				result += '  <tr>';
				result += '     <td><h3>Transfer domain</h3></td>';
				result += '  </tr>';
				result += '  <tr>';
				result += '    <td>Transfer domain to <select id="owner"></select>';
				result += ' <input type="button" onclick="transferDomain('+jsonData.domain.id+', document.getElementById(\'owner\').value);" value="transfer" /></td>';
				result += '  </tr>';
				result += '</table></p>';
			}
			
			document.getElementById('body').innerHTML = result;
			if (lastAddedType) document.getElementById("new[type]").value = lastAddedType;
		}
	}
}

function saveAllRecords (input)
{
	var postBody = 'dummy=true';
	
	for(i=0; i<input.length; i++)
	{
		var ident = input[i].id;
		var value = input[i].value;
		
		if(ident == "domainId")
		{
			var domainId = value;
		}
		if (ident.match(/name\[/)) value += currentEditedDomain.domain.name;
		
		postBody += '&'+encodeURIComponent(ident)+'='+encodeURIComponent(value);
	}
	
	new Ajax.Request(baseurl+"?p=saveAllRecords",
		{
			method:"post",
			postBody:postBody,
			asynchronous:true,
			onSuccess:succesFailed,
			onFailure:resultError
		});
	
	setTimeout('editDomain('+domainId+');', timeoutInMilisec);
}

function transferDomain (domainId, owner)
{
	new Ajax.Request(baseurl+"?p=transferDomain",
        {
            method:"post",
            postBody:"domainId="+encodeURIComponent(domainId)+"&owner="+encodeURIComponent(owner),
            asynchronous:true,
            onSuccess:succesFailed,
            onFailure:resultError
        });
}

function removeRecord (recordId, domainId)
{
	if(confirm("Are you sure you want to delete the record?"))
	{
		new Ajax.Request(baseurl+"?p=removeRecord",
		{
			method:"post",
			postBody:"recordId="+encodeURIComponent(recordId)+"&domainId="+encodeURIComponent(domainId),
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
		postBody:"domainId="+encodeURIComponent(domainId)+"&recordId="+encodeURIComponent(recordId)+"&name="+encodeURIComponent(name + currentEditedDomain.domain.name)+"&type="+encodeURIComponent(type)+"&content="+encodeURIComponent(content)+"&prio="+encodeURIComponent(prio)+"&ttl="+encodeURIComponent(ttl),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function newRecord (domainId, name, type, content, prio, ttl)
{
	lastAddedName = name; lastAddedType = type; lastAddedContent = content;
	new Ajax.Request(baseurl+"?p=newRecord", 
	{
		method:"post",
		postBody:"domainId="+encodeURIComponent(domainId)+"&name="+encodeURIComponent(name + currentEditedDomain.domain.name)+"&type="+encodeURIComponent(type)+"&content="+encodeURIComponent(content)+"&prio="+encodeURIComponent(prio)+"&ttl="+encodeURIComponent(ttl),
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
	result += '	 <td colspan="2"><h3>Add a domain</h3></td>';
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
	result += '	 <td colspan="2"><h3>Add multiple domains</h3></td>';
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
		postBody:"domain="+encodeURIComponent(domain)+"&webIP="+encodeURIComponent(webIP)+"&mailIP="+encodeURIComponent(mailIP)+"&owner="+encodeURIComponent(owner)+"&template="+encodeURIComponent(template)+"&type=NATIVE",
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
		postBody:"domains="+encodeURIComponent(domains)+"&webIP="+encodeURIComponent(webIP)+"&mailIP="+encodeURIComponent(mailIP)+"&owner="+encodeURIComponent(owner)+"&template="+encodeURIComponent(template),
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
	updateHash('#admin=user');
}

function showUserAdmin (request)
{
	if (request.readyState==4)
	{
		var jsonData = eval('('+request.responseText+')');
		
		var result = '<form name="addUserrrr"><table width="800">';
		result += '<tr><td colspan=3><h3>User list</h3></td></tr>';
		
		for(i=0; i<jsonData.length; i++)
		{
			if(!(userlevel<5 && jsonData[i].id != myUserId))
			{
				result += '<tr>';
				result += '  <td>[ <a onclick="deleteUser('+jsonData[i].id+');setTimeout(\'userAdmin();\', '+timeoutInMilisec+');">delete user</a> ]</td>';
				result += '  <td><a href="javascript:editUser('+jsonData[i].id+');">'+jsonData[i].fullname+'</a></td>';
				result += '  <td>'+jsonData[i].level+'</td>';
				result += '</tr>';
			}
		}
		
		result += '<tr><td colspan="3">&nbsp;</td></tr>';
		result += '<tr><td colspan="3"><h3>Add a user</h3></td></tr>';
		result += '<tr><td>Username</td><td colspan="2"><input type="text" id="username"></td></tr>';
		result += '<tr><td>Password</td><td colspan="2"><input type="text" id="password"></td></tr>';
		result += '<tr><td>Password check</td><td colspan="2"><input type="text" id="passwordcheck"></td></tr>';
		result += '<tr><td>Full name</td><td colspan="2"><input type="text" id="fullname"></td></tr>';
		result += '<tr><td>E-mail</td><td colspan="2"><input type="text" id="email"></td></tr>';
		result += '<tr><td>Description</td><td colspan="2"><input type="text" id="description"></td></tr>';
		result += '<tr><td>Max domains</td><td colspan="2"><input type="text" id="maxdomains" value="0"></td></tr>';
		result += '<tr><td>Level</td><td colspan="2"><select id="level"><option value="1" selected="selected">normal user</option>';
		result += '<option value="5">moderator</option><option value="10">administrator</option></td></tr>';
		result += '<tr><td>Active</td><td colspan="2">Yes <input type="radio" name="activeBool" id="activeBool1" value="1" checked="checked" /> No <input type="radio" name="activeBool" id="activeBool0" value="0" /></td></tr>';
		result += '<tr><td colspan="3"><input type="button" id="save" value="Add user" onclick="addUser(';
		result += ' document.getElementById(\'username\').value, document.getElementById(\'password\').value, document.getElementById(\'passwordcheck\').value,';
		result += ' document.getElementById(\'fullname\').value, document.getElementById(\'email\').value, document.getElementById(\'description\').value,';
		result += ' document.getElementById(\'level\').value, checkActiveBool(document.getElementById(\'activeBool1\'),document.getElementById(\'activeBool0\')),document.getElementById(\'maxdomains\').value);setTimeout(\'userAdmin();\', '+timeoutInMilisec+');"></td></tr>';
		result += '</table></form>';
		
		document.getElementById("body").innerHTML = result;
	}
}

function checkActiveBool (bool0, bool1)
{
	if (bool0.checked)
	{
		return bool0.value;
	}else
	{
		return bool1.value;
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
	console.log("editUser",userId);
	new Ajax.Request(baseurl+"?p=getUser", 
	{
		method:"post",
		postBody:"userId="+userId,
		asynchronous:true,
		onSuccess:showEditUser,
		onFailure:resultError
	});
	updateHash('#user=' + userId);
}

function showEditUser (request)
{
	console.log("showEditUser",request);
	if (request.readyState==4)
	{
		var jsonData = JSON.parse(request.responseText);
		console.log(jsonData);
		editUser_data = jsonData;
		try{editUser_u2ftokens=JSON.parse(jsonData.u2fdata);}catch(ex){editUser_u2ftokens=[];}
		if(!editUser_u2ftokens || !editUser_u2ftokens.length) editUser_u2ftokens=[];

		var result = '<h3>Edit user :: <b>'+jsonData.username+'</b></h3><table width="800"><input type="hidden" id="userId" value="'+jsonData.id+'">';
		result += '<tr><td>Username</td><td><input type="text" id="username" value="'+jsonData.username+'"></td></tr>';
		result += '<tr><td>Password</td><td><input type="password" id="password" value=""></td></tr>';
		result += '<tr><td>Full name</td><td><input type="text" id="fullname" value="'+jsonData.fullname+'"></td></tr>';
		result += '<tr><td>E-mail</td><td><input type="text" id="email" value="'+jsonData.email+'"></td></tr>';
		result += '<tr><td>Description</td><td><textarea id="description">'+jsonData.description+'</textarea></td></tr>';
		result += '<tr><td>Max domains</td><td colspan="2"><input type="text" id="maxdomains" value="'+jsonData.maxdomains+'"></td></tr>';
		result += '<tr><td>Level</td><td><input type="text" id="level" value="'+jsonData.level+'"></td></tr>';
		result += '<tr><td>Active</td><td><input type="text" id="active" value="'+jsonData.active+'"></td></tr>';
		result += '</table>';
		result += '<div style="border: 1px solid #ddd; padding: 10px">';
		result += "<ul>";
		for(var i=0; i<editUser_u2ftokens.length; i++) {
			var token = editUser_u2ftokens[i];
			result += "<li>";
			for(var key in token) if (token.hasOwnProperty(key)) result += "<b>"+key+"</b>="+token[key]+"<br>";
			result += "<input type='button' value='remove' onclick='removeU2fKey("+i+");'></li>";
		}
		result += "</ul>";
		result += '<div id="u2f-status"><input type="button" name="save" value="Add U2F key" onclick="addU2fKey();"></div>';
		result += '</div>';

		result += '<hr><input type="button" name="save" value="Apply" onclick="saveUserFromForm();">';
		result += '<hr>';
		
		document.getElementById("body").innerHTML = result;
	}
}
function addU2fKey() {
	document.getElementById("u2f-status").innerHTML="Press your U2F key to add it to your account...";
	var appId = editUser_data.u2f_req.appId;
	var registerRequests = [{version: editUser_data.u2f_req.version, challenge: editUser_data.u2f_req.challenge}];
		u2f.register(appId, registerRequests, editUser_data.u2f_sigs, function(signature) {
		if (!editUser_u2ftokens) editUser_u2ftokens = [];
		editUser_u2ftokens.push(signature);
		document.getElementById("u2f-status").innerHTML="U2F key added, click Apply to store";
	});
}
function removeU2fKey(index) {
	editUser_u2ftokens.splice(index,1);
	saveUserFromForm();
}

function addUser (username, password, passwordcheck, fullname, email, description, level, active, maxdomains)
{
	new Ajax.Request(baseurl+"?p=addUser", 
	{
		method:"post",
		postBody:"username="+username+"&password="+password+"&passwordcheck="+passwordcheck+"&fullname="+fullname+"&email="+email+"&description="+description+"&level="+level+"&active="+active+"&maxdomains="+maxdomains,
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function saveUserFromForm() {
	saveUser(document.getElementById('userId').value, document.getElementById('username').value, document.getElementById('password').value,
		document.getElementById('fullname').value, document.getElementById('email').value, document.getElementById('description').value,
		document.getElementById('level').value, document.getElementById('active').value, document.getElementById('maxdomains').value);
}
function saveUser (userId, username, password, fullname, email, description, level, active, maxdomains)
{
	new Ajax.Request(baseurl+"?p=editUser", 
	{
		method:"post",
		postBody:"userId="+userId+"&username="+username+"&password="+password+"&fullname="+fullname+"&email="+email+"&description="+description+"&level="+level+"&active="+active+"&maxdomains="+maxdomains+"&u2fdata="+JSON.stringify(editUser_u2ftokens),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function loginForm ()
{
	var result  = '<p><form method="post" action=\'javascript:login(document.getElementById("usernamefield").value, document.getElementById("passwordfield").value);\'><table>';
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
	result += '	<td colspan="2"><input type="submit" value="login" tabindex="3" id="loginBtn" /></td>';
	result += '  </tr>';
	result += '</table></form></p>';
	document.getElementById('login').innerHTML = result;
	document.getElementById('usernamefield').focus();
}

function login (username, password)
{
	new Ajax.Request(baseurl+"?p=doLogin", 
	{
		method:"post",
		postBody:"username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password),
		asynchronous:true,
		onSuccess:succesFailed,
		onFailure:resultError
	});
}

function doU2fSignature(req) {
	setTimeout(function() {
		u2f.sign(req.challenge[0].appId, req.challenge[0].challenge, req.challenge, function(data) {
			document.getElementById("login").innerHTML="Login successful, redirecting ...";
			new Ajax.Request(baseurl+"?p=checkU2f", {
				method:"post", postBody: "username="+encodeURIComponent(req.username)+"&auth="+encodeURIComponent(JSON.stringify(data)),
				asynchronous:true, onSuccess:succesFailed, onFailure:resultError
			});
		});
		document.getElementById("login").innerHTML="<h2>Please touch U2F device...</h2>";
	}, 1);
}
