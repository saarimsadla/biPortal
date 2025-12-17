function setCTYP(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("C_Type").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=ctyp');

}

function setMarket(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){
        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Market").innerHTML = out;
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    var e = document.getElementById("C_Type");
    var ct = e.options[e.selectedIndex].value;
    var u = document.getElementById("USER_DROP_DOWN");
    var usr = u.options[u.selectedIndex].value;
    if(ct != "") {
        if (usr != "") {
            xhr.send('ct=' + ct + '&usr=' + usr + '&lov_nme=market');
        } else {
            alert("Please select user first");
        }
    }
    else{
        alert("Please select C Type first");
    }
}

function setContract(t){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("C_Type");
    var ct = e.options[e.selectedIndex].value;
    var u = document.getElementById("USER_DROP_DOWN");
    var usr = u.options[u.selectedIndex].value;
    var m = document.getElementById("Market");
    var mkt = m.options[m.selectedIndex].value;
    var y = document.getElementById("year").value;
    //frm_mde
    var frm_mde = document.getElementById("frm_mde").value;
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){
        var out = "";
        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(frm_mde);
            if(frm_mde != 'new' && frm_mde != 'dup') {
                document.getElementById("Contarct_Number").value = out;
                document.getElementById("Contarct_Code").value = ct + '/' + mkt + '-' + out + '/' + y;
            }
            //alert(t);

            if(t != "DUP") {
                setType();
            }
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if(ct != "") {
        if (usr != "") {
            if (mkt != "0") {
                xhr.send('ct=' + ct + '&usr=' + usr +'&mrkt='+ mkt +'&lov_nme=cnt_num');
            } else {
                alert("Please select market first");
            }
        } else {
            alert("Please select user first");
        }
    }
    else{
        alert("Please select C Type first");
    }

}

function setSubContract(){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("C_Type");
    var ct = e.options[e.selectedIndex].value;
    var u = document.getElementById("USER_DROP_DOWN");
    var usr = u.options[u.selectedIndex].value;
    var m = document.getElementById("Market");
    var mkt = m.options[m.selectedIndex].value;
    var y = document.getElementById("year").value;
    var sb = document.getElementById("Revision");
    var sbc = sb.options[sb.selectedIndex].value;
    //frm_mde
    var frm_mde = document.getElementById("frm_mde").value;

    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){
        var out = "";
        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(frm_mde);
            if(frm_mde != 'new' && frm_mde != 'dup') {
                document.getElementById("Contarct_Number").value = out;
                document.getElementById("Contarct_Code").value = ct + '/' + mkt + '-' + out + '/' + y + ' ' + sbc;
            }
            setType();
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if(ct != "") {
        if (usr != "") {
            if (mkt != "") {
                xhr.send('ct=' + ct + '&usr=' + usr +'&mrkt='+ mkt +'&lov_nme=cnt_num');
            } else {
                alert("Please select market first");
            }
        } else {
            alert("Please select user first");
        }
    }
    else{
        alert("Please select C Type first");
    }

}

function setType(){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("C_Type");
    var ct = e.options[e.selectedIndex].value;

    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Type").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if(ct != "") {
        xhr.send('ct=' + ct +'&lov_nme=typ');
    }else{
        alert("Please select C Type first");
    }

}

function setYarn(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Yarn").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=yarn');

}

function setUnit(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Unit").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=unt');

}

function setProcess(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Process").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=prcss');

}
							
 function setCustomer(){
    var xhr = new XMLHttpRequest();
     var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
     xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Customer").innerHTML = out;

        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=cust');

}
							
function setVac(){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("Vendor");
    var cac = e.options[e.selectedIndex].value;
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Category").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('cac='+cac+'&lov_nme=vac');

}
						
function setConsignee(){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("Vendor");
    var cac = e.options[e.selectedIndex].value;
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Consignee").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    setcustAdrs();
    xhr.send('lov_nme=cnsgn');

}
						
function setST(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'.'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Shipment_Type").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    setconsAdrs();
    xhr.send('lov_nme=shpmnt_typ');

}
							
function setSM(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Shipment_Mode").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=shpmnt_mde');

}
							
function setPrt(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Port").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=prt');

}
							
function setOT(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'.'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Order_Type").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=ot');

}
								
function setPT(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Payment_Term").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=pt');

}
							
function setWve(t){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Weave").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=wv');
//gid set function calls here
    setWarp();
    setGID(t);
    setGIDFI();
}

function setGID(t){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            if(t!= '') {
                document.getElementById("GID").innerHTML = out;
                setGCC();
            }else{
                document.getElementById("GID").innerHTML = out;
            }
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    //alert(t);
    if(t != null){
        xhr.send('lov_nme=gid&g_num='+t);
    }
    else {
        xhr.send('lov_nme=gid&g_num=-1');
    }
}


function setGIDFI(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("GID_FI").innerHTML = out;

            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    //alert(t);
      xhr.send('lov_nme=gid&g_num=-1');

}

function setComp(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+''+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Composition").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=comp');

}
						
function setMecFin(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Mechanical_Finish").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=mec_fin');

}
							
function setChemFin(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Chemical_Finish").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=chem_fin');

}
							
function setSlvdg(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'.'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Selvedge").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=slvdg');

}
							
function setUOM(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Unit_Of_Measurement").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=uom');

}
						
function setCurr(val){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+''+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Currency").innerHTML = out;
            //set pice lenght, packing type and shipment samples with defualt values here
            setDefualts(val)
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=curr');

}

function setDefualts(val){
//val 1 == yds and 2 == mtrs
    //alert(val);
    if (val == 1 ){
        document.getElementById("Piece_Length").value = "Min 80% 60 Yards & up and max 20% 30-59 Yards";
        document.getElementById("Packing").value = "R.O.T 100-150 Yards per roll & max 2 pieces per roll";
        document.getElementById("Shipments_Samples").value = "5 Yards from Shade \"A\" and 2 Yards each for balance Shade \"B\" & \"C\"";
    }
    else if (val == 2 ){
        document.getElementById("Piece_Length").value = "Min 80% 60 Meters & up and max 20% 30-59 Meters";
        document.getElementById("Packing").value = "R.O.T 100-150 Meters per roll & max 2 pieces per roll";
        document.getElementById("Shipments_Samples").value = "5 Meters from Shade \"A\" and 2 Meters each for balance Shade \"B\" & \"C\"";
    }
}

function setFT(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Fabric_Type").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=ft');

}
						
function setEU(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("End_Use").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=eu');

}
						
function setAT(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Agent").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=agnt');
    setPackInstrctn();
    setDP();
    setAgTyp();
    setCT();
}
							
function setAgTyp(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

if(this.readyState == 4 && this.status == 200)
{
    console.log(xhr.responseText);

    var out = "";
    out += '<option value="'+'6'+'" class="active">'+'Select'+'</option>';
    out += xhr.responseText
    //alert(out);
document.getElementById("Agent_Type").innerHTML = out;
//Type
}
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=at');

}
						
function setCT(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Commision_Type").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=ct');
    //setClr();
}

function setClr(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Color").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=clr');
    setBulkProc();
}


function setDP(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Dyeing_Process").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=dp');

}
							
function setSCT(){
var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

xhr.onreadystatechange  = function(){

    if(this.readyState == 4 && this.status == 200)
    {
        console.log(xhr.responseText);

        var out = "";
        out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
        out += xhr.responseText
        //alert(out);
        document.getElementById("Shade_Category").innerHTML = out;
        //Type
    }
}
/* As the function was bound to the input you can use `this` to get the value */

xhr.send('lov_nme=sc');
    setBulkProc();
}

function setBulkProc(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("BULK_PROCESS").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=bulk_proc');

}

function setGCC(){
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("GID");
    var gid = e.options[e.selectedIndex].value;

    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){
        var out = "";
        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(out);
            document.getElementById("Greige_Construction").value = out;
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if(gid != "") {
        xhr.send('gid=' + gid +'&lov_nme=grg_cons');
    }
    else{
        alert("Please select GID first");
    }

}

function setGCFI() {
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("GID_FI");
    var gid = e.options[e.selectedIndex].value;
    var url = "http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        var out = "";
        if (this.readyState == 4 && this.status == 200) {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(out);
            document.getElementById("Gregie_Construction_FI").value = out;
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if (gid != "") {
        xhr.send('gid=' + gid + '&lov_nme=grg_cons');
    } else {
        alert("Please select GID first");
    }
}
    function setWarp(){
        var xhr = new XMLHttpRequest();
        var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
        xhr.open( 'POST', url, true );
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange  = function(){

            if(this.readyState == 4 && this.status == 200)
            {
                console.log(xhr.responseText);

                var out = "";
                out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
                out += xhr.responseText
                //alert(out);
                document.getElementById("Warp").innerHTML = out;
                //Type
            }
        }
        /* As the function was bound to the input you can use `this` to get the value */

        xhr.send('lov_nme=wrp');

    }

    function setWeft(){
        var xhr = new XMLHttpRequest();
        var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
        xhr.open( 'POST', url, true );
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange  = function(){

            if(this.readyState == 4 && this.status == 200)
            {
                console.log(xhr.responseText);

                var out = "";
                out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
                out += xhr.responseText
                //alert(out);
                document.getElementById("Weft").innerHTML = out;
                //Type
            }
        }
        /* As the function was bound to the input you can use `this` to get the value */

        xhr.send('lov_nme=wft');

    }

    function setEnds(){
        var xhr = new XMLHttpRequest();
        var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
        xhr.open( 'POST', url, true );
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange  = function(){

            if(this.readyState == 4 && this.status == 200)
            {
                console.log(xhr.responseText);

                var out = "";
                out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
                out += xhr.responseText
                //alert(out);
                document.getElementById("Ends").innerHTML = out;
                //Type
            }
        }
        /* As the function was bound to the input you can use `this` to get the value */

        xhr.send('lov_nme=end');

    }

    function setPics(){
        var xhr = new XMLHttpRequest();
        var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
        xhr.open( 'POST', url, true );
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange  = function(){

            if(this.readyState == 4 && this.status == 200)
            {
                console.log(xhr.responseText);

                var out = "";
                out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
                out += xhr.responseText
                //alert(out);
                document.getElementById("Pics").innerHTML = out;
                //Type
            }
        }
        /* As the function was bound to the input you can use `this` to get the value */

        xhr.send('lov_nme=pics');

    }

    function setCutableWidth(){
        var xhr = new XMLHttpRequest();
        var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
        xhr.open( 'POST', url, true );
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange  = function(){

            if(this.readyState == 4 && this.status == 200)
            {
                console.log(xhr.responseText);

                var out = "";
                out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
                out += xhr.responseText
                //alert(out);
                document.getElementById("Cuttable_Width").innerHTML = out;
                //Type
            }
        }
        /* As the function was bound to the input you can use `this` to get the value */

        xhr.send('lov_nme=cwdth');

    }

function setPackInstrctn(){
    var xhr = new XMLHttpRequest();
    var url ="http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open( 'POST', url, true );
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange  = function(){

        if(this.readyState == 4 && this.status == 200)
        {
            console.log(xhr.responseText);

            var out = "";
            out += '<option value="'+'0'+'" class="active">'+'Select'+'</option>';
            out += xhr.responseText
            //alert(out);
            document.getElementById("Pack_Instructions").innerHTML = out;
            document.getElementById("Pack_Instructions_1").innerHTML = out;
            //Type
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */

    xhr.send('lov_nme=pck_i');

}

function setConstruction() {
    var st ='N';
   // var wr = document.getElementById("Warp");
    var wrp = document.getElementById("Warp").value;//wr.options[wr.selectedIndex].value;

//    var wf = document.getElementById("Weft");
    var wft = document.getElementById("Weft").value;//wf.options[wf.selectedIndex].value;

//    var e = document.getElementById("Ends");
    var ends = document.getElementById("Ends").value;//e.options[e.selectedIndex].value;

//    var pc = document.getElementById("Pics");
    var pics = document.getElementById("Pics").value;//pc.options[pc.selectedIndex].value;

   // var cw = document.getElementById("Cuttable_Width");
    var cwdth = document.getElementById("Cuttable_Width").value;//cw.options[cw.selectedIndex].value;

    if (wrp != "") {
        st = 'Y';
    } else {
        st = 'N';
        alert("Please add Warp first");
    }

    if (wft != "") {
        st = 'Y';
    } else {
        st = 'N';
        alert("Please add Weft first");
    }
    if (ends != "") {
        st = 'Y';
    } else {
        st = 'N';
        alert("Please add Ends first");
    }
    if (pics != "") {
        st = 'Y';
    } else {
        st = 'N';
        alert("Please add Pics first");
    }
    if (cwdth != "") {
        st = 'Y';
    } else {
        st = 'N';
        alert("Please add cuttable width first");
    }

    if(st == 'Y')
    {
        var out = wrp+"x"+wft+"/"+ends+"X"+pics+" "+cwdth;
        //alert(out);
        document.getElementById("Construction_FI").value = out;
    }



}

function setcustAdrs() {
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("Category");
    var cst = e.options[e.selectedIndex].value;
    var url = "http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        var out = "";
        if (this.readyState == 4 && this.status == 200) {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            if(out) {
                document.getElementById("Shipment_Address").value = out;
            }
            else{
                document.getElementById("Shipment_Address").value = '.';
            }
        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if (cst != "") {
        xhr.send('CUSTMR=' + cst + '&lov_nme=custAdrs');
    } else {
        alert("Please select Customer first");
    }
}

function setconsAdrs() {
    var xhr = new XMLHttpRequest();
    var e = document.getElementById("Consignee");
    var cnsgn = e.options[e.selectedIndex].value;
    var url = "http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        var out = "";
        if (this.readyState == 4 && this.status == 200) {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(out);
            if(out) {
                document.getElementById("Consignee_Address").value = out;
            }else{
                document.getElementById("Consignee_Address").value = '.';
            }

        }
    }
    /* As the function was bound to the input you can use `this` to get the value */
    if (cnsgn != "") {
        //alert(cnsgn);
        xhr.send('cnsgn=' + cnsgn + '&lov_nme=cnsgnAdrs');
    } else {
        alert("Please select Consignee first");
    }
}

function lockRecord(contract) {
    var xhr = new XMLHttpRequest();
    var url = "http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        var out = "";
        if (this.readyState == 4 && this.status == 200) {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(out);
            if(out=='SUCCESS') {
                alert("Record saved succesfully for contract "+contract);
                window.location.href = "order-sheet-temp.php?mode=INSERT&Contarct_Code="+contract;
            }else{
               alert ("Unable to save record, ERR#CJS");
            }

        }
    }
    xhr.send('cntrct=' + contract + '&lov_nme=lockRec');

}

function checkColor(val) {
    //alert(val.value);
    var xhr = new XMLHttpRequest();
    var clr = val.value;
    var contract = document.getElementById("cntrct_hidden").value;
    //alert(contract);
    var url = "http://".concat(window.location.hostname).concat(":85/test_ndot/config/lov_pop.php");
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        var out = "";
        if (this.readyState == 4 && this.status == 200) {
            console.log(xhr.responseText);

            var out = "";
            out += xhr.responseText
            //alert(out);
            if(out > 0) {
                alert("Please select different color, same colors not allowed");
               val.value="";
               val.focus();
            }

        }
    }
    xhr.send('clr=' + clr + '&cntrct='+contract+'&lov_nme=checkRec');

}

//onblur="checkSet(this)"
function checkSet(val){
    console.log(val.value);
    if (val.value == null || val.value == "" || val.value <= 0)
    {
        //alert(val.type);
        if(val.type =="text")
        {
            val.value = ".";
        }
        else{
            if(val.value != "0") {
                val.value = "0";
            }
        }

    }
}

function checkSetNul(val){
    console.log(val.value);
    if (val.value == null || val.value == "." || val.value <= 0)
    {
        //alert(val.type);
        val.value = "";
    }
}

function setPAD() {
    var grd = new Date(document.getElementById("GRD").value);

    var month = grd.getMonth(); // getMonth() returns month from 0-11 not 1-12
    var year = grd.getFullYear();//  returns year of given date
    var day =(grd.getDate()+11);// Since getDate() returns Date from 0-30 or 0-29 not 1-30 or 1-31

        var out = new Date(year,month,day); //create new date to be assigned
        //alert(out.toISOString().split('T')[0]);
        document.getElementById("PAD").value = out.toISOString().split('T')[0];// assign date in iso format as displayed as such.

}
function setValuesCommission(val){
    if(val.value == 1){//quantity basis consider rate not percentage
        document.getElementById("FDD%").value = 0;
    }
    else if(val.value == 2){//values basis consider percentage not rate
        document.getElementById("Rate").value = 0;
    }

}

/*
    function setConstruction() {
        var st ='N';
        var wr = document.getElementById("Warp");
        var wrp = wr.options[wr.selectedIndex].value;

        var wf = document.getElementById("Weft");
        var wft = wf.options[wf.selectedIndex].value;

        var e = document.getElementById("Ends");
        var ends = e.options[e.selectedIndex].value;

        var pc = document.getElementById("Pics");
        var pics = pc.options[pc.selectedIndex].value;

        var cw = document.getElementById("Cuttable_Width");
        var cwdth = cw.options[cw.selectedIndex].value;

        if (wrp != "") {
            st = 'Y';
        } else {
            st = 'N';
            alert("Please select Warp first");
        }

        if (wft != "") {
            st = 'Y';
        } else {
            st = 'N';
            alert("Please select Warp first");
        }
        if (ends != "") {
            st = 'Y';
        } else {
            st = 'N';
            alert("Please select Warp first");
        }
        if (pics != "") {
            st = 'Y';
        } else {
            st = 'N';
            alert("Please select Warp first");
        }
        if (cwdth != "") {
            st = 'Y';
        } else {
            st = 'N';
            alert("Please select Warp first");
        }

        if(st == 'Y')
        {
            var out = wrp+"x"+wft+"/"+ends+"X"+pics+" "+cwdth;
            //alert(out);
            document.getElementById("Construction_FI").value = out;
        }



}
*/
