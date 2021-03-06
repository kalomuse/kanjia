$list = $('.uploader-list');
// 初始化Web Uploader
var uploader = WebUploader.create({

    // 选完文件后，是否自动上传。
    auto: true,

    // swf文件路径
    swf: '/public/js/webuploader/webuploader/Uploader.swf',

    // 文件接收服务端。
    //server: '/base/upload/img?id=' + id + '&type=' + type,

    // 选择文件的按钮。可选。
    // 内部根据当前运行是创建，可能是input元素，也可能是flash.
    pick: '#filePicker',

    // 只允许选择图片文件。
    accept: {
        title: 'Images',
        extensions: 'gif,jpg,jpeg,bmp,png',
        mimeTypes: 'image/*'
    }
});

// 当有文件添加进来的时候
uploader.on( 'fileQueued', function( file ) {
    var $li = $(
        '<li><a><button type="button" onclick="remove_img(this)" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button><img/></button></li></a>'
    ),$img = $li.find('img');
    if(img_type == 'return_first_pic')
        $list.html($li);
    else if(img_type == 'return_pic')
        $list.append( $li );


    // $list为容器jQuery实例


    // 创建缩略图
    // 如果为非图片文件，可以不用调用此方法。
    // thumbnailWidth x thumbnailHeight 为 100 x 100
    uploader.makeThumb( file, function( error, src ) {
        if ( error ) {
            $img.replaceWith('<span>不能预览</span>');
            return;
        }

        $img.attr( 'src', src );
    }, 100, 100 );
});
// 文件上传过程中创建进度条实时显示。
uploader.on( 'uploadProgress', function( file, percentage ) {
    var $li = $list,
        $percent = $li.find('.progress span');

    // 避免重复创建
    if ( !$percent.length ) {
        $percent = $('<p class="progress"><span></span></p>')
            .appendTo( $li )
            .find('span');
    }

    $percent.css( 'width', percentage * 100 + '%' );
});

// 文件上传成功，给item添加成功class, 用样式标记上传成功。
uploader.on( 'uploadSuccess', function( file, res ) {
    if(res && res.path) {
        if(res.type == 'first_pic') {
            first_pic = res.path;
            $('.intr-img-list .close').remove();
        } else if(res.type == 'pic') {
            pic += res.path + ',';
        }
    }
});

// 文件上传失败，显示上传出错。
uploader.on( 'uploadError', function( file ) {
    var $li = $list,
        $error = $li.find('div.error');

    // 避免重复创建
    if ( !$error.length ) {
        $error = $('<div class="error"></div>').appendTo( $li );
    }

    $error.text('上传失败');
});

// 完成上传完了，成功或者失败，先删除进度条。
uploader.on( 'uploadComplete', function( file ) {
    $list.find('.progress').remove();
});

uploader.on("error",function (type){
    if(type == "F_DUPLICATE"){
        alert("请不要重复选择文件！");
    }else if(type == "Q_EXCEED_SIZE_LIMIT"){
        alert("系统提示" + "<span class='C6'>所选附件总大小</span>不可超过<span class='C6'>" + 2 + "M</span>哦！<br>换个小点的文件吧！");
    } else if(type == "Q_TYPE_DENIED") {
        alert('请选择图片格式');
    }

});