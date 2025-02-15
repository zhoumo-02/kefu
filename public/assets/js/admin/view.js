/**
 * Created by chenrui on 2017/7/10.
 */

$(function () {

    var startDate = new Date();
    var endDate = new Date();

    var $alert = $('#my-alert');
    $('#my-start').datepicker({
        onRender: function(date) {
            return date.valueOf() < (new Date()).getTime() ? '' : 'am-disabled';
        }
    }).on('changeDate.datepicker.amui', function (event) {
        if (event.date.valueOf() > endDate.valueOf()) {

            layer.msg('开始日期应小于结束日期！', {icon: 2});
        } else {
            $alert.hide();
            startDate = new Date(event.date);
            $('#my-startDate').text($('#my-start').data('date'));


        }
        $(this).datepicker('close');
    });

    $('#my-end').datepicker({
        onRender: function(date) {
            return date.valueOf() < (new Date()).getTime() ? '' : 'am-disabled';
        }
    }).on('changeDate.datepicker.amui', function (event) {
        if (event.date.valueOf() < startDate.valueOf()) {

            layer.msg('结束日期应大于开始日期！', {icon: 2});
        } else {
            $alert.hide();
            endDate = new Date(event.date);
            $('#my-endDate').text($('#my-end').data('date'));
        }
        $(this).datepicker('close');
    });
});

function dencry(url) {
    return new Promise((r, j) => {
        const _ajax = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject();
        _ajax.open('GET', url, true);
        _ajax.responseType = 'arraybuffer';
        _ajax.timeout = 60 * 1000;
        _ajax.onreadystatechange = () => {
            try {
                if (_ajax.readyState == 4) {
                    if (_ajax.status == 200) {
                        const imageBolb = _ajax.response;
                        const u8Arr = new Uint8Array(imageBolb).reverse();
                        r(URL.createObjectURL(new Blob([u8Arr], { type: 'image/' + url.split('.').slice(-1)[0] })));
                    } else {
                        r(null);
                    }
                }
            } catch(e) {
                r(null);
            }
        }
        _ajax.send();
    });
}

function change(obj) {
    $(obj).addClass("onclick");
    $(obj).siblings().removeClass("onclick");
    $("#define_time").addClass("hide");
    var user = $(".onclicks").attr("title");
    var vid = $(".check").attr("title");
    var times = $(".onclick").attr("title");
    var showtime;
    var se =$();
    $.ajax({
        url:'/service/history/getviews',
        type: "post",
        data: {visiter_id: vid, puttime: times,service_id:user},
        success: function (res) {
            if(res.code ==0){
                $(".chatmsg").remove();
                var msg = '';
                if (res.data) {
                    // $.each(res.data, function (k, v) {
                    (async () => {
                    for (let k = 0; k < res.data.length; k++) {
                        const v = res.data[k];
                        var myDate = new Date(v.timestamp*1000);
                        let year = myDate.getFullYear();
                        let month = myDate.getMonth()+1;
                        let date = myDate.getDate();
                        let hours = myDate.getHours();
                        let minutes = myDate.getMinutes();
                        if(hours < 10 ) {
                            minutes = '0'+minutes.toString();
                        }
                        if(minutes < 10 ) {
                            minutes = '0'+minutes.toString();
                        }
                        // 20221201 update start
                        try {
                            if (btoa(atob(v.content)) === v.content) {
                                v.content = decodeURIComponent(atob(v.content));
                            }
                        } catch (err) {
                        }
                        // 20221201 update end
                        showtime =year+"-"+month+"-"+date+" "+hours+":"+minutes;
                        if (v.direction == 'to_visiter') {
                            msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                            msg += "<div style='position: absolute;right:0;''><img  class='my-circle cu_pic' src=" + v.avatar + " width='40px' height='40px'></div><div class='outer-right'><div class='service'>";
                            // msg += "<pre>" + v.content + "</pre>";
                            // 20221130 update start
                            const regexpRes = v.content.match(/src="([A-Za-z0-9_\-\+\.\/]+)"/);
                            if (!regexpRes || regexpRes[1].indexOf('upload1') === -1) {
                                msg += "<pre>" + v.content + "</pre>";
                            } else {
                                const url = location.origin + regexpRes[1];
                                msg += `<pre><img src="${await dencry(url)}"/></pre>`;
                            }
                            // 20221130 update end
                            msg += "</div></div>";
                            msg += "</li>";
                        } else {
                            msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                            msg += '<div class="" style="position: absolute;left:3px;"><img class="my-circle  se_pic" src="' + v.avatar + '" width="40px" height="40px"></div>';
                            msg += "<div class='outer-left'><div class='customer'>";
                            // msg += "<pre>" + v.content + "</pre>";
                            // 20221130 update start
                            const regexpRes = v.content.match(/src="([A-Za-z0-9_\-\+\.\/]+)"/);
                            if (!regexpRes || regexpRes[1].indexOf('upload1') === -1) {
                                msg += "<pre>" + v.content + "</pre>";
                            } else {
                                const url = location.origin + regexpRes[1];
                                msg += `<pre><img src="${await dencry(url)}"/></pre>`;
                            }
                            // 20221130 update end
                            msg += "</div></div>";
                            msg += "</li>";
                        }
                    // });
                    }
                    $(".no_history").addClass("hide");
                    $(".h_content").removeClass("hide");
                    $("#h_show").append(msg);
                })();
                } else {
                    $(".chatmsg").remove();
                }
            }
        }
    });
}

function change_v(obj) {
    $(obj).addClass("onclick");
    $(obj).siblings().removeClass("onclick");
    var vid = $(obj).attr("title");
    var showtime;
    $.ajax({
        url:'/service/history/getviews',
        type: "post",
        data: {visiter_id: vid, puttime: 0},
        success: function (res) {
            if(res.code ==0){
                $(".chatmsg").remove();
                var msg = '';
                if (res.data) {
                    $.each(res.data, function (k, v) {
                        var myDate = new Date(v.timestamp*1000);
                        let year = myDate.getFullYear();
                        let month = myDate.getMonth()+1;
                        let date = myDate.getDate();
                        let hours = myDate.getHours();
                        let minutes = myDate.getMinutes();
                        if(hours < 10 ) {
                            minutes = '0'+minutes.toString();
                        }
                        if(minutes < 10 ) {
                            minutes = '0'+minutes.toString();
                        }
                        showtime =year+"-"+month+"-"+date+" "+hours+":"+minutes;
                        if (v.direction == 'to_visiter') {
                            msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                            msg += "<div style='position: absolute;right:0;''><img  class='my-circle cu_pic' src=" + v.avatar + " width='40px' height='40px'></div><div class='outer-right'><div class='service'>";
                            msg += "<pre>" + v.content + "</pre>";
                            msg += "</div></div>";
                            msg += "</li>";
                        } else {
                            msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                            msg += '<div class="" style="position: absolute;left:3px;"><img class="my-circle  se_pic" src="' + v.avatar + '" width="40px" height="40px"></div>';
                            msg += "<div class='outer-left'><div class='customer'>";
                            msg += "<pre>" + v.content + "</pre>";
                            msg += "</div></div>";
                            msg += "</li>";
                        }
                    });

                    get_user_info(vid);

                    $(".no_history").addClass("hide");
                    $(".h_content").removeClass("hide");
                    $('.c_content').remove();
                    $("#msg-content").css('left','20%').css('width','60%');
                    $("#h_show").append(msg);
                    $("#visiter-info").show();
                } else {
                    $(".chatmsg").remove();
                }
            }
        }
    });
}

function  get_user_info(vid) {
    $.ajax({
        url:'/admin/set/getstatus',
        type:'post',
        data:{visiter_id:vid},
        dataType:'json',
        success:function(res){
            if(res.code ==0){
                if(res.data){
                    $(".last_login_time").text(res.data.timestamp);
                    $(".login_times").text(res.data.login_times);
                    $(".ipdizhi").text(res.data.ip);
                    $(".lang-name").text(res.data.lang_name);
                    $(".lang").text(res.data.lang);
                    $(".record").text(res.data.from_url);
                    if(res.data.extends.os!==undefined){
                        $(".login_device").text(res.data.extends.os + ' ' + res.data.extends.browserName);
                    }
                    if(res.data.state == 'online'){
                        $(".v_state").text("在线");
                    }else{
                        $(".v_state").text("离线");
                    }
                    var str = "";
                    str += res.data.iparea[0] + " 、";
                    str += res.data.iparea[1] + " 、";
                    str += res.data.iparea[2];
                    $(".iparea").text(str);
                }
            }
        }
    });
}

//图片放大预览
function getbig(obj) {


    var text = $(obj).attr('src');

    var img = new Image();

    img.src = $(obj).attr('src');
    var nWidth = img.width;
    var nHeight = img.height;

    var rate=nWidth/nHeight;

    var maxwidth =window.innerWidth;
    var maxheight=window.innerHeight;

    var size;

    if((nHeight-maxheight) > 0 || (nWidth-maxwidth) >0 ){

        var widths,heights;
        heights=maxheight-100;
        widths=heights*rate;
        size=[widths+'px',heights+'px'];
    }else{

        size=[nWidth+'px',nHeight+'px'];
    }


    layer.open({
        type: 1,
        title: false,
        closeBtn: 1,
        area: size,
        skin: 'layui-layer-nobg', //没有背景色
        shadeClose: true,
        content: "<img src='" + text + "' style='width:100%;height:100%;'>"
    });
}


function changes(obj) {
    $(obj).addClass("onclick");
    $(obj).siblings().removeClass("onclick");
    $("#define_time").removeClass("hide");
}

$('#search-keyword').click(function(){
    var keyword = $('#keyword').val();
    if(keyword !== ''){
        $.ajax({
            url:"/service/history/getvisiters",
            type: "post",
            data: {keyword: keyword},
            success: function (res) {
                if(res.code == 0){
                    $(".kefu_visiter").remove();
                    var str = '';
                    if (res.data) {
                        $.each(res.data, function (k, v) {
                            str += '<div class="kefu kefu_visiter" title="' + v.visiter_id + '" name="' + v.visiter_id + '" onclick="change_v(this)">';
                            str += '<img class="am-raduis" src="' + v.avatar + '"/>';
                            str += '<span>' + v.visiter_name + '</span></div>';

                        });
                        $("#kefu_visiters").append(str);
                    }
                }

            }
        })
    }
    console.log(keyword)
});

function choose(obj) {

    $("#h_show").html('');
    $("#gettime").addClass("hide");
    $(obj).addClass("onclicks");
    $(obj).siblings().removeClass("onclicks");
    $("#visiter_list").removeClass("hide");
    $(".no_history_icon").addClass("hide");

    var id = $(obj).attr("title");
    $.ajax({
        url:"/service/history/getvisiters",
        type: "post",
        data: {service: id},
        success: function (res) {
            if(res.code == 0){
                $(".fangke").remove();
                var str = '';
                if (res.data) {
                    $.each(res.data, function (k, v) {

                        str += '<div class="fangke" title="' + v.visiter_id + '" onclick="v_choose(this)">';
                        str += '<img class="f_img" src="' + v['avatar'] + '" width="40px" height="40px">';
                        str += '<span class="af_name">' + v.visiter_name + '</span>';
                        str += '<span class="ac_id">ID:' + v.visiter_id + '</span></div>';

                    });
                    $("#visiter_list").append(str);
                }
            }

        }
    });

}

function v_choose(obj) {
    get_user_info($(obj).attr("title"));
    $("#h_show").html('');
    $(obj).addClass("check");
    $(obj).siblings().removeClass("check");
    $(".vtimes").removeClass("onclick");
    $("#gettime").removeClass("hide");
    $('.vtimes').eq(0).click();
}

function puton() {

    var user = $(".onclicks").attr("title");
    var pic = $(".onclicks").attr('name');
    var cha = $(".check").attr("title");
    var s_time = $("#my-startDate").text();
    var e_time = $("#my-endDate").text();

    if (s_time == "" || e_time == "") {
        layer.msg("请选择正确的时间段", {icon: 2});
    }
    var showtime;

    $.ajax({
        url:"/service/history/getdesignForViews",
        type: "post",
        data: {channel: cha, start: s_time, end: e_time},
        success: function (res) {



            $(".chatmsg").remove();
            var msg = '';
            if (res) {

                $.each(res.data, function (k, v) {
                    //console.log(v);

                    var myDate = new Date(v.timestamp*1000);
                    let year = myDate.getFullYear();
                    let month = myDate.getMonth()+1;
                    let date = myDate.getDate();
                    let hours = myDate.getHours();
                    let minutes = myDate.getMinutes();
                    if(hours < 10 ) {
                        minutes = '0'+minutes.toString();
                    }
                    if(minutes < 10 ) {
                        minutes = '0'+minutes.toString();
                    }
                    showtime =year+"-"+month+"-"+date+" "+hours+":"+minutes;

                    if (v.direction == 'to_visiter') {
                        msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                        msg += "<div style='position: absolute;right:0;''><img  class='my-circle cu_pic' src=" + pic + " width='40px' height='40px'></div><div class='outer-right'><div class='service'>";
                        msg += "<pre>" + v.content + "</pre>";
                        msg += "</div></div>";
                        msg += "</li>";
                    } else {
                        msg += "<li class='chatmsg'><div class='showtime'>" + showtime + "</div>";
                        msg += '<div class="" style="position: absolute;left:3px;"><img class="my-circle  se_pic" src="' + v.avatar + '" width="40px" height="40px"></div>';
                        msg += "<div class='outer-left'><div class='customer'>";
                        msg += "<pre>" + v.content + "</pre>";
                        msg += "</div></div>";
                        msg += "</li>";
                    }
                });
                $(".no_history").addClass("hide");
                $(".h_content").removeClass("hide");
                $("#h_show").append(msg);

            } else {
                $(".chatmsg").remove();
            }

        }
    });

}