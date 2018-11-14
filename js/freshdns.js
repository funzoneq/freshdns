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

function escapeHTML(str) {
	if (str===undefined || str===null) return "";
	return (""+str).replace(/'/g, "&apos;").replace(/"/g, "&quot;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function buildQuery(params) {
	return Object.keys(params).map(function(key) {
		return encodeURIComponent(key) + "=" + encodeURIComponent(params[key]);
	}).join("&");
}

function apiGet(functionCall, query, callbackFunction) {
	apiCall("GET", functionCall, query, null, callbackFunction);
}
function apiPost(functionCall, postParameters, callbackFunction) {
	apiCall("POST", functionCall, {}, postParameters, callbackFunction);
}
function apiCall(method, functionCall, getParameters, postParameters, callbackFunction) {
	if (!callbackFunction) callbackFunction = succesFailed;
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		try {
			if (xhr.readyState === XMLHttpRequest.DONE) {
				if (xhr.status === 200) {
					try {
						callbackFunction(xhr);
					} catch(ex) {
						console.warn(ex);
						message('danger', 'Unhandled Exception in callback', ex);
					}
				} else if (xhr.getResponseHeader("Content-Type") == "application/json") {
					var jsonData = JSON.parse(xhr.responseText);
					message("danger", xhr.statusText, jsonData.text);
				} else if (xhr.getResponseHeader("Content-Type") == "application/json") {
					message('danger', 'Error ' + xhr.status + ' -- ' + xhr.statusText + ' -- ' + xhr.responseText);
				}
			}
		} catch(ex) {
			console.warn(ex);
			message('danger', 'Unhandled Exception in API helper', ex);
		}
	};
	xhr.open(method, baseurl+"?p="+encodeURIComponent(functionCall)+"&" + buildQuery(getParameters));
	if (!postParameters) {
		xhr.send();
	} else {
		postParameters['xsrf_token'] = window.xsrf_token;
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(buildQuery(postParameters));
	}
}

function resetActive() {
	$("li.active").removeClass("active");
}

function message(style,text,subtext) {
	if (!subtext) subtext = "";
	console.info("MESSAGE:",style,text,subtext)
	var msg = document.getElementById("message");
	msg.innerHTML = "<div class='alert alert-"+style+"'><strong>" + escapeHTML(text) + "</strong><br>" + escapeHTML(subtext) + "</div>";
	msg.onclick = function() { msg.innerHTML = ""; }
}

function succesFailed (request)
{
	var jsonData = JSON.parse(request.responseText);

	if(jsonData.status=="success")
	{
		message("success", jsonData.text);
	} else
	{
		message("danger", "Failed: "+jsonData.text);
	}
	
	if(jsonData.reload=="yes")
	{
		window.location.reload();
	}

	if (jsonData.u2f_challenge)
	{
		doU2fSignature(jsonData.u2f_challenge);
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
	updateHash('#list=' + letter);
	apiGet("letterlist", { "letter":letter }, function (request) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = JSON.parse(request.responseText);
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
	});
}

function liveSearchStart()
{
	apiGet("livesearch", { "q":document.getElementById('livesearch').value }, function (request) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = JSON.parse(request.responseText);
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
	});
}

function ownersList (userId)
{
	apiGet("ownerslist", { "userId":userId }, function (request) {
		document.getElementById("body").innerHTML = '';
		
		var jsonData = JSON.parse(request.responseText);
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
	});
}

function deleteZone (domainId)
{
	if(confirm("Are you sure you want to delete this domain?"))
	{
		apiPost("deleteZone", { "domainId": domainId });
	}
}

function editDomain (domainId)
{
	updateHash('#domain=' + domainId);
	apiGet("getDomainInfo", { "domainId":encodeURIComponent(domainId) }, function(request) {
		var jsonData = currentEditedDomain = JSON.parse(request.responseText);
		
		apiGet("getOwners", {}, changeOwnersSelect);

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
			result += '<td><input type="text" value="'+escapeHTML(r.name.replace(name, ''))+'" id="name['+i+']"><input type="hidden" value="'+r.id+'" id="id['+i+']"></td>';
			result += '<td><input type="text" size="6" class="type" value="'+escapeHTML(r.type)+'" id="type['+i+']"></td>';
			result += '<td><input type="text" size="50" value="'+escapeHTML(r.content)+'" id="content['+i+']"></td>';
			result += '<td><input type="text" size="2" class="num" value="'+escapeHTML(r.prio)+'" id="prio['+i+']"></td>';
			result += '<td><input type="text" size="4" class="num" value="'+escapeHTML(r.ttl)+'" id="ttl['+i+']"></td>';
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
		result += '    <tr><td><input type="text" value="'+escapeHTML(lastAddedName?lastAddedName:r.name.replace(name,''))+'" id="new[name]" /></td>';
		result += '    <td><select id="new[type]"><option selected="selected" value="A">A</option>';
		result += '    <option value="AAAA">AAAA</option><option value="CNAME">CNAME</option>';
		result += '    <option value="HINFO">HINFO</option><option value="MX">MX</option>';
		result += '    <option value="NAPTR">NAPTR</option><option value="NS">NS</option>';
		result += '    <option value="PTR">PTR</option><option value="SOA">SOA</option>';
		result += '    <option value="TXT">TXT</option><option value="URL">URL</option>';
		result += '    <option value="SRV">SRV</option><option value="MBOXFW">MBOXFW</option></select></td>';
		result += '	   <td><input type="content" size="50" value="'+escapeHTML(lastAddedContent)+'" id="new[content]" /></td>';
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
	});
}

function saveAllRecords (input)
{
	var postBody = {};
	
	for(i=0; i<input.length; i++)
	{
		var ident = input[i].id;
		var value = input[i].value;
		
		if(ident == "domainId")
		{
			var domainId = value;
		}
		if (ident.match(/name\[/)) value += currentEditedDomain.domain.name;
		
		postBody[ident] = value;
	}
	
	apiPost("saveAllRecords", postBody);
	
	setTimeout('editDomain('+domainId+');', timeoutInMilisec);
}

function transferDomain (domainId, owner)
{
	apiPost("transferDomain", { "domainId": domainId, "owner": owner });
}

function removeRecord (recordId, domainId)
{
	if(confirm("Are you sure you want to delete the record?"))
	{
		apiPost("removeRecord", { "domainId": domainId, "recordId": recordId });
	}
}

function saveRecord(domainId, recordId, name, type, content, prio, ttl)
{
	apiPost("saveRecord", {
		"domainId": domainId, "recordId": recordId,
		"name": name + currentEditedDomain.domain.name,
		"type": type, "content": content, "prio": prio, "ttl": ttl
	});
}

function newRecord (domainId, name, type, content, prio, ttl)
{
	lastAddedName = name; lastAddedType = type; lastAddedContent = content;
	apiPost("newRecord", {
		"domainId": domainId,
		"name": name + currentEditedDomain.domain.name,
		"type": type, "content": content, "prio": prio, "ttl": ttl
	});
}

function newDomain ()
{
	apiGet("getOwners", {}, changeOwnersSelect);
	apiGet("getTemplates", {}, changeTemplateSelect);
	
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
	apiGet("getOwners", {}, changeOwnersSelect);
	apiGet("getTemplates", {}, changeTemplateSelect);
	
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
	var jsonData = JSON.parse(request.responseText);
	
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

function changeTemplateSelect (request)
{
	var jsonData = JSON.parse(request.responseText);
	
	document.getElementById('template').options.length=0;
	
	for(i=0; i<jsonData.length; i++)
	{
		document.getElementById('template').options[i]=new Option(jsonData[i], jsonData[i], false, false);
	}
}

function saveNewRecord (domain, webIP, mailIP, owner, template)
{
	apiPost("newDomain", {
		"domain": domain,
		"webIP": webIP, "mailIP": mailIP,
		"owner": owner, "template": template, "type": "NATIVE"
	});
}

function saveNewDomains (domains, webIP, mailIP, owner, template)
{
	apiPost("newDomains", {
		"domains": domains,
		"webIP": webIP, "mailIP": mailIP,
		"owner": owner, "template": template, "type": "NATIVE"
	});
}

function generatePassword ()
{
	document.getElementById('password').value = Math.random().toString(36).slice(-10);
	document.getElementById('passwordcheck').value = document.getElementById('password').value;
}

function userAdmin ()
{
	updateHash('#admin=user');
	apiGet("getOwners", {}, function (request) {
		var jsonData = JSON.parse(request.responseText);
		
		var result = '<form name="addUserrrr"><table width="800">';
		result += '<tr><td colspan=3><h3>User list</h3></td></tr>';
		
		for(i=0; i<jsonData.length; i++)
		{
			if(!(userlevel<5 && jsonData[i].id != myUserId))
			{
				result += '<tr>';
				result += '  <td>[ <a onclick="deleteUser('+jsonData[i].id+');setTimeout(\'userAdmin();\', '+timeoutInMilisec+');">delete user</a> ]</td>';
				result += '  <td><a href="javascript:editUser('+jsonData[i].id+');">'+escapeHTML(jsonData[i].fullname)+'</a></td>';
				result += '  <td>'+jsonData[i].level+'</td>';
				result += '</tr>';
			}
		}
		
		result += '<tr><td colspan="3">&nbsp;</td></tr>';
		result += '<tr><td colspan="3"><h3>Add a user</h3></td></tr>';
		result += '<tr><td>Username</td><td colspan="2"><input type="text" id="username"></td></tr>';
		result += '<tr><td>Password</td><td colspan="2"><input type="text" id="password"> <input type="button" value="generate" onclick="generatePassword()"></td> </tr>';
		result += '<tr><td>Password check</td><td colspan="2"><input type="text" id="passwordcheck"></td></tr>';
		result += '<tr><td>Full name</td><td colspan="2"><input type="text" id="fullname"></td></tr>';
		result += '<tr><td>E-mail</td><td colspan="2"><input type="text" id="email"></td></tr>';
		result += '<tr><td>Description</td><td colspan="2"><input type="text" id="description"></td></tr>';
		result += '<tr><td>Max domains</td><td colspan="2"><input type="text" id="maxdomains" value="0"></td></tr>';
		result += '<tr><td>Level</td><td colspan="2"><select id="level"><option value="1" selected="selected">normal user</option>';
		result += '<option value="5">moderator</option><option value="10">administrator</option></td></tr>';
		result += '<tr><td>Active</td><td colspan="2">Yes <input type="radio" name="activeBool" id="activeBool1" value="1" checked="checked" /> No <input type="radio" name="activeBool" id="activeBool0" value="0" /></td></tr>';
		result += '<tr><td colspan="3"><input type="button" id="save" value="Add user" onclick="addUser();"></td></tr>';
		result += '</table></form>';
		
		document.getElementById("body").innerHTML = result;
	});
}

function deleteUser (userId)
{
	if(confirm("Are you sure you want to delete this user, and all it's data?"))
	{
		apiPost("deleteUser", {
			"userId": userId
		});
	}
}

function editUser (userId)
{
	console.log("editUser",userId);
	apiPost("getUser", { "userId": userId }, function (request)
	{
		console.log("showEditUser",request);
		var jsonData = JSON.parse(request.responseText);
		console.log(jsonData);
		editUser_data = jsonData;
		try{editUser_u2ftokens=JSON.parse(jsonData.u2fdata);}catch(ex){editUser_u2ftokens=[];}
		if(!editUser_u2ftokens || !editUser_u2ftokens.length) editUser_u2ftokens=[];

		var result = '<h3>Edit user :: <b>'+jsonData.username+'</b></h3><table width="800"><input type="hidden" id="userId" value="'+jsonData.id+'">';
		result += '<tr><td>Username</td><td><input type="text" id="username" value="'+escapeHTML(jsonData.username)+'"></td></tr>';
		result += '<tr><td>Password</td><td><input type="password" id="password" value=""></td></tr>';
		result += '<tr><td>Full name</td><td><input type="text" id="fullname" value="'+escapeHTML(jsonData.fullname)+'"></td></tr>';
		result += '<tr><td>E-mail</td><td><input type="text" id="email" value="'+escapeHTML(jsonData.email)+'"></td></tr>';
		result += '<tr><td>Description</td><td><textarea id="description">'+escapeHTML(jsonData.description)+'</textarea></td></tr>';
		result += '<tr><td>Max domains</td><td colspan="2"><input type="text" id="maxdomains" value="'+jsonData.maxdomains+'"></td></tr>';
		result += '<tr><td>Level</td><td><input type="text" id="level" value="'+jsonData.level+'"></td></tr>';
		result += '<tr><td>Active</td><td><input type="text" id="active" value="'+jsonData.active+'"></td></tr>';
		result += '</table>';
		result += '<div style="border: 1px solid #ddd; padding: 10px">';
		result += "<ul>";
		for(var i=0; i<editUser_u2ftokens.length; i++) {
			var token = editUser_u2ftokens[i];
			result += "<li>";
			for(var key in token) if (token.hasOwnProperty(key)) result += "<b>"+escapeHTML(key)+"</b>="+escapeHTML(token[key])+"<br>";
			result += "<input type='button' value='remove' onclick='removeU2fKey("+i+");'></li>";
		}
		result += "</ul>";
		result += '<div id="u2f-status"><input type="button" name="save" value="Add U2F key" onclick="addU2fKey();"></div>';
		result += '</div>';

		result += '<hr><input type="button" name="save" value="Apply" onclick="saveUserFromForm();">';
		result += '<hr>';
		
		document.getElementById("body").innerHTML = result;
	});
	updateHash('#user=' + userId);
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

function addUser ()
{
	if (document.getElementById('passwordcheck').value !== document.getElementById('password').value) {
		alert("Password and password check don't match.");
		return;
	}
	apiPost("addUser", {
		"username": document.getElementById('username').value,
		"password": document.getElementById('password').value,
		"fullname": document.getElementById('fullname').value,
		"email": document.getElementById('email').value,
		"description": document.getElementById('description').value,
		"level": document.getElementById('level').value,
		"active": document.getElementById('activeBool1').checked?'1':'0',
		"maxdomains": document.getElementById('maxdomains').value,
	});
	setTimeout('userAdmin();', timeoutInMilisec);
}

function saveUserFromForm() {
	apiPost("editUser", {
		'userId': document.getElementById('userId').value,
		'username': document.getElementById('username').value,
		'password': document.getElementById('password').value,
		'fullname': document.getElementById('fullname').value,
		'email': document.getElementById('email').value,
		'description': document.getElementById('description').value,
		'level': document.getElementById('level').value,
		'active': document.getElementById('active').value,
		'maxdomains': document.getElementById('maxdomains').value,
		'u2fdata': JSON.stringify(editUser_u2ftokens)
	});
}

function loginForm ()
{
	var result  = '<p><form method="post" action=\'javascript:login();\'><table>';
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
	apiPost("doLogin", {
		"username": document.getElementById("usernamefield").value,
		"password": document.getElementById("passwordfield").value
	});
}

function doU2fSignature(req) {
	setTimeout(function() {
		u2f.sign(req.challenge[0].appId, req.challenge[0].challenge, req.challenge, function(data) {
			document.getElementById("login").innerHTML="Login successful, redirecting ...";
			apiPost("checkU2f", {
				"username": req.username,
				"auth": JSON.stringify(data)
			});
		});
		document.getElementById("login").innerHTML="<h2>Please touch U2F device...</h2>";
	}, 1);
}
