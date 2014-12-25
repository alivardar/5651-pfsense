
/* ---- Variables ---- */
var actb_timeOut = -1; // Autocomplete Timeout in ms (-1: autocomplete never time out)
var actb_lim = 5;    // Number of elements autocomplete can show (-1: no limit)
var actb_firstText = false; // should the auto complete be limited to the beginning of keyword?
/* ---- Variables ---- */

/* --- Styles --- */
var actb_bgColor = '#FFFFFF';
var actb_textColor = '#000000';
var actb_hBgColor = '#990000';
var actb_htextColor = '#FFFFFF';
var actb_fFamily = 'Courier';
var actb_fSize = '14px';
var actb_hStyle = 'text-decoration:underline;font-weight="bold"';
/* --- Styles --- */

/* ---- Constants ---- */
var actb_keywords = new Array();
var actb_display = true;
var actb_pos = 0;
var actb_total = 0;
var actb_curr = null;
var actb_rangeu = 0;
var actb_ranged = 0;
var actb_bool = new Array();
var actb_pre = 0;
var actb_toid;
var actb_tomake = true;
/* ---- Constants ---- */

function actb_parse(n){
    var t = escape(actb_curr.value);
    var tobuild = '';
    var i;
    var re = "";

    if (actb_firstText){
        re = new RegExp("^" + t, "i");
    }else{
        re = new RegExp(t, "i");
    }
    var p = n.search(re);

    for (i=0;i<p;i++){
        tobuild += n.substr(i,1);
    }
    tobuild += "";
    for (i=p;i<t.length+p;i++){
        tobuild += n.substr(i,1);
    }
    tobuild += "";
    for (i=t.length+p;i<n.length;i++){
        tobuild += n.substr(i,1);
    }
    return tobuild;
}
function actb_generate(){
    if(navigator.appName == "Microsoft Internet Explorer") {
        if(window.location.protocol == 'https:') return;
        if(actb_keywords.length < 1) return; // if no items setup, do not invoke code.  prevent ie crashing.
        if (document.getElementById('tat_frame')) document.body.removeChild(document.getElementById('tat_frame'));
    }
    if (document.getElementById('tat_table')) document.body.removeChild(document.getElementById('tat_table'));
    if(navigator.appName == "Microsoft Internet Explorer") {
        frame = document.createElement('iframe');
        frame.cellSpacing='1px';
        frame.cellPadding='2px';
        frame.style.zIndex = 3;
        frame.style.position='absolute';
        frame.style.top = eval(curTop() + actb_curr.offsetHeight) + "px";
        frame.style.left = curLeft() + "px";
        frame.style.backgroundColor=actb_bgColor;
        frame.id = 'tat_frame';
    }
    a = document.createElement('table');
    a.cellSpacing='1px';
    a.cellPadding='2px';
    a.style.border = '1px solid #990000';
    a.style.zIndex = 5;
    a.style.position='absolute';
    a.style.top = eval(curTop() + actb_curr.offsetHeight) + "px";
    a.style.left = curLeft() + "px";
    a.style.backgroundColor=actb_bgColor;
    a.id = 'tat_table';
    if(navigator.appName == "Microsoft Internet Explorer")
        document.body.appendChild(frame);
    document.body.appendChild(a);
    var i;
    var first = true;
    var j = 1;
    var height = 0;
    var counter = 0;
    for (i=0;i<actb_keywords.length;i++){
        if (actb_bool[i]){
            counter++;
            r = a.insertRow(-1);
            if (first && !actb_tomake){
                r.style.backgroundColor = actb_hBgColor;
                first = false;
                actb_pos = counter;
            }else if(actb_pre == i){
                r.style.backgroundColor = actb_hBgColor;
                first = false;
                actb_pos = counter;
            }else{
                r.style.backgroundColor = actb_bgColor;
                r.style.color = actb_bgColor;
            }
            r.id = 'tat_tr'+(j);
            c = r.insertCell(-1);
            c.style.color = actb_textColor;
            counter = 0;
            first = true;
            if (first && !actb_tomake){
                c.style.color = actb_htextColor;
                first = false;
                actb_pos = counter;
            }else if(actb_pre == i){
                c.style.color = actb_htextColor;
                first = false;
                actb_pos = counter;
            }else{
                c.style.color = actb_textColor;
            }
            c.style.fontFamily = actb_fFamily;
            a.style.zIndex = 5;
            c.style.fontSize = actb_fSize;
            c.innerHTML = actb_parse(actb_keywords[i]);
            c.id = 'tat_td'+(j);
            height = height + c.offsetHeight;
            width = c.offsetWidth;
            j++;
        }
        if (j - 1 == actb_lim && j < actb_total){
            r = a.insertRow(-1);
            r.style.backgroundColor = actb_bgColor;
            c = r.insertCell(-1);
            c.style.color = actb_textColor;
            a.style.zIndex = 5;
            c.style.fontFamily = 'arial narrow';
            c.style.fontSize = actb_fSize;
            c.align='center';
            c.innerHTML = '\\/';
            break;
        }
    }
    actb_rangeu = 1;
    actb_ranged = j-1;
    actb_display = true;
    if (actb_pos <= 0) actb_pos = 1;
    if(navigator.appName == "Microsoft Internet Explorer") {
        frame.width=width;
        frame.height=height + 5;
    }
}
function curTop(){
    actb_toreturn = 0;
    obj = actb_curr;
    while(obj){
        actb_toreturn += obj.offsetTop;
        obj = obj.offsetParent;
    }
    return actb_toreturn;
}
function curLeft(){
    actb_toreturn = 0;
    obj = actb_curr;
    while(obj){
        actb_toreturn += obj.offsetLeft;
        obj = obj.offsetParent;
    }
    return actb_toreturn;
}
function actb_remake() {
    document.body.removeChild(document.getElementById('tat_table'));
    if(navigator.appName == "Microsoft Internet Explorer") {
        document.body.removeChild(document.getElementById('tat_frame'));
        frame = document.createElement('iframe');
        frame.cellSpacing='2px';
        frame.cellPadding='3px';
        frame.style.position='absolute';
        frame.style.zIndex = 5;
        frame.style.top = eval(curTop() + actb_curr.offsetHeight) + "px";
        frame.style.left = curLeft() + "px";
        frame.style.backgroundColor=actb_bgColor;
        frame.id = 'tat_frame';
    }
    a = document.createElement('table');
    a.cellSpacing='2px';
    a.cellPadding='3px';
    a.style.position='absolute';
    a.style.zIndex = 5;
    a.style.top = eval(curTop() + actb_curr.offsetHeight) + "px";
    a.style.left = curLeft() + "px";
    a.style.backgroundColor=actb_bgColor;
    a.id = 'tat_table';
    if(navigator.appName == "Microsoft Internet Explorer")
        document.body.appendChild(frame);
    document.body.appendChild(a);
    var width = 0;
    var height = 0;
    var i;
    var first = true;
    var j = 1;
    if (actb_rangeu > 1){
        r = a.insertRow(-1);
        r.style.backgroundColor = actb_bgColor;
        c = r.insertCell(-1);
        c.style.color = actb_textColor;
        c.style.fontFamily = 'arial narrow';
        a.style.zIndex = 5;
        c.style.fontSize = actb_fSize;
        c.align='center';
        c.innerHTML = '/\\';
    }
    for (i=0;i<actb_keywords.length;i++){
        if (actb_bool[i]){
            if (j >= actb_rangeu && j <= actb_ranged){
                r = a.insertRow(-1);
                r.style.backgroundColor = actb_bgColor;
                r.id = 'tat_tr'+(j);
                c = r.insertCell(-1);
                c.style.color = actb_textColor;
                c.style.fontFamily = actb_fFamily;
                a.style.zIndex = 5;
                c.style.fontSize = actb_fSize;
                c.innerHTML = actb_parse(actb_keywords[i]);
                c.id = 'tat_td'+(j);
                j++;
            }else{
                j++;
            }
        }
        if (j > actb_ranged) break;
    }
    if (j-1 < actb_total){
        r = a.insertRow(-1);
        r.style.backgroundColor = actb_bgColor;
        c = r.insertCell(-1);
        c.style.color = actb_textColor;
        c.style.fontFamily = 'arial narrow';
        c.style.fontSize = actb_fSize;
        c.align='center';
        c.innerHTML = '\\/';
        height = height + c.height;
        width = c.width;
    }
    if(navigator.appName == "Microsoft Internet Explorer") {
        frame.width=width;
        frame.height=height;
    }
}
function actb_goup(){
    if (!actb_display) return;
    if (actb_pos == 1) return;
    document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_bgColor;
    document.getElementById('tat_td'+actb_pos).style.color = actb_textColor;
    actb_pos--;
    if (actb_pos < actb_rangeu) actb_moveup();
    document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_hBgColor;
    document.getElementById('tat_td'+actb_pos).style.color = actb_htextColor;
    if (actb_toid) clearTimeout(actb_toid);
    if (actb_timeOut > 0) actb_toid = setTimeout("actb_removedisp()",actb_timeOut);
}
function actb_godown(){
    if (!actb_display) return;
    if (actb_pos == actb_total) return;
    document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_bgColor;
    document.getElementById('tat_td'+actb_pos).style.color = actb_textColor;
    actb_pos++;
    if (actb_pos > actb_ranged) actb_movedown();
    document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_hBgColor;
    document.getElementById('tat_td'+actb_pos).style.color= actb_htextColor;
    if (actb_toid) clearTimeout(actb_toid);
    if (actb_timeOut > 0) actb_toid = setTimeout("actb_removedisp()",actb_timeOut);
}
function actb_movedown(){
    actb_rangeu++;
    actb_ranged++;
    actb_remake();
}
function actb_moveup(){
    actb_rangeu--;
    actb_ranged--;
    actb_remake();
}
function actb_penter(){
    if (!actb_display) return;
    actb_display = 0;
    var word = '';
    var c = 0;
    for (var i=0;i<=actb_keywords.length;i++){
        if (actb_bool[i]) c++;
        if (c == actb_pos){
            word = actb_keywords[i];
            break;
        }
    }
    a = word;//actb_keywords[actb_pos-1];//document.getElementById('tat_td'+actb_pos).;
    actb_curr.value = a;
    actb_removedisp();
}
function actb_removedisp(){
    actb_display = 0;
    if(navigator.appName == "Microsoft Internet Explorer")
        if (document.getElementById('tat_frame')) document.body.removeChild(document.getElementById('tat_frame'));
    if (document.getElementById('tat_table')) document.body.removeChild(document.getElementById('tat_table'));
    if (actb_toid) clearTimeout(actb_toid);
}
function actb_checkkey(evt, sndr){
    a = evt.keyCode;
    if (a == 38){ // up key
        actb_goup();
    }else if(a == 40){ // down key
        actb_godown();
    }else if(a == 13){
        actb_penter();
    }
}
function actb_tocomplete(sndr,evt,arr){
    if (arr) actb_keywords = arr;
    if (evt.keyCode == 38 || evt.keyCode == 40 || evt.keyCode == 13) return;
    var i;
    if (actb_display){
        var word = 0;
        var c = 0;
        for (i=0;i<=actb_keywords.length;i++){
            if (actb_bool[i]) c++;
            if (c == actb_pos){
                word = i;
                break;
            }
        }
        actb_pre = word;//actb_pos;
    }else{ actb_pre = -1; }

    if (!sndr) sndr = evt.srcElement;
    actb_curr = sndr;

    if (sndr.value == ''){
        actb_removedisp();
        return;
    }
    var t = sndr.value;
    var re = "";
    if (actb_firstText){
        re = new RegExp("^" + t, "i");
    }else{
        re = new RegExp(t, "i");
    }

    actb_total = 0;
    actb_tomake = false;
    for (i=0;i<actb_keywords.length;i++){
        actb_bool[i] = false;
        if (re.test(actb_keywords[i])){
            actb_total++;
            actb_bool[i] = true;
            if (actb_pre == i) actb_tomake = true;
        }
    }
    if (actb_toid) clearTimeout(actb_toid);
    if (actb_timeOut > 0) actb_toid = setTimeout("actb_removedisp()",actb_timeOut);
    actb_generate(actb_bool);
}
