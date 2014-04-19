/* * ************************************************************ 
 * 
 * Date: Mar 1, 2013
 * version: 1.0
 * programmer: Shani Mahadeva <satyashani@gmail.com>
 * Description:   
 * Javascript file mod_form.js
 * 
 * 
 * *************************************************************** */

function pad(number, length){
    var str = "" + number;
    while (str.length < length) {
        str = '0'+str;
    }
    return str;
}

M.mod_fuzebox = {};
M.mod_fuzebox.init = function(Y) {
    var offset = new Date().getTimezoneOffset();
    Y.one("input[name='timezone']").set('value',offset);
}
M.mod_fuzebox.attendeesvalidate = function(value){
    var regex = /^([A-z]+ [A-z]+ <[A-z0-9._%+-]+@[A-z0-9.-]+\.[A-z]{2,4}>[,|\n]*)*$/;
    return regex.test(value);
}
M.mod_fuzebox.loadInfo = function(Y,url,mid){
    Y.io(url,{
            method: 'GET',
            on: {
                success: function (id, result) {
                    var json = Y.JSON.parse(result.responseText);
                    var invitees = json.meeting.details.invitees;
                    var inviteeshtml = "";
                    for(var i in invitees){
                        inviteeshtml += invitees[i].name+" &lt;"+invitees[i].email+"&gt;<br />";
                    }
                    Y.one('#fuzeattendees_'+mid).set('innerHTML', inviteeshtml);
                    var info = "";
                    if(json.meeting.details.timezone)
                        info += "<div class='fuze_row'><span class='title'>Timezone: </span>"+json.meeting.details.timezone+"<br /></div>";
                    info += "<div class='fuze_row'><span class='title'>Webinar: </span>"+(json.meeting.details.timezone?"Yes":"No")+"<br /></div>";
                    info += "<div class='fuze_row'><span class='title'>Auto-Record: </span>"+(json.meeting.details.timezone?"Yes":"No")+"<br /></div>";
                    if(json.meeting.details.toll_number)
                        info += "<div class='fuze_row'><span class='title'>Toll Number: </span>"+json.meeting.details.toll_number+"<br /></div>";
                    if(json.meeting.details.toll_free_number)
                        info += "<div class='fuze_row'><span class='title'>Toll Free Number: </span>"+json.meeting.details.toll_free_number+"<br /></div>";
                    if(json.meeting.details.attendee_pin)
                        info += "<div class='fuze_row'><span class='title'>Attendee Pin: </span>"+json.meeting.details.attendee_pin+"<br /></div>";
                    if(json.meeting.details.moderator_pin)
                        info += "<div class='fuze_row'><span class='title'>Moderator Pin: </span>"+json.meeting.details.moderator_pin+"<br /></div>";
                    Y.one('#info_'+mid).set('innerHTML', info);
                    
                    var media = json.meeting.medialist;
                    var mediahtml = "";
                    for(var i in media){
                        mediahtml +="<div class='fuze_row'><b>"+media[i].filename+"</b> , "+Math.floor(media[i].size/1024)+"Kb</div>";
                    }
                    Y.one('#meetingmedia_'+mid).set('innerHTML', mediahtml);
                },
                failure: function(id,response){
                    Y.one('#fuzeattendees_'+mid).set('innerHTML', "Failed to load info from fusebox site");
                }
            }
        })
}
