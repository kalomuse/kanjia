$file_li = $('.file-list');
var fileuploader = WebUploader.create({
    auto: true,
    server: '/index/upload/file',
    // swf文件路径
    swf: '/public/js/plugins/webuploader/Uploader.swf',
    fileSizeLimit: 5*1024*1024,

    // 选择文件的按钮。可选。
    // 内部根据当前运行是创建，可能是input元素，也可能是flash.
    pick: '#picker',

    // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
    resize: false,
    accept: {// 只允许选择图片文件格式
        extensions: 'mp3',
    },
});
// 当有文件被添加进队列的时候
fileuploader.on( 'fileQueued', function( file ) {
    $file_li.html( '<div id="' + file.id + '" class="item">' +
        '<h4 class="info">' + file.name + '</h4>' +
        '<p class="state">等待上传...</p>' +
        '</div>' );
});

// 文件上传过程中创建进度条实时显示。
fileuploader.on( 'uploadProgress', function( file, percentage ) {
    var $percent = $file_li.find('.progress .progress-bar');

    // 避免重复创建
    if ( !$percent.length ) {
        $percent = $('<div class="progress progress-striped active">' +
            '<div class="progress-bar" role="progressbar" style="width: 0%">' +
            '</div>' +
            '</div>').appendTo( $file_li ).find('.progress-bar');
    }

    $file_li.find('p.state').text('上传中');

    $percent.css( 'width', percentage * 100 + '%' );
});

fileuploader.on( 'uploadSuccess', function( file, res ) {
    $file_li.find('p.state').text('上传完毕');
    if(res && res.path) {
        bg_music = res.path;
    }
});

fileuploader.on( 'uploadError', function( file ) {
    $file_li.find('p.state').text('上传出错');
});

fileuploader.on( 'uploadComplete', function( file ) {
    $file_li.find('.progress').fadeOut();
});

fileuploader.on("error",function (type){
    if(type == "F_DUPLICATE"){
        alert("请不要重复选择文件！");
    }else if(type == "Q_EXCEED_SIZE_LIMIT"){
        alert("系统提示","<span class='C6'>所选附件总大小</span>不可超过<span class='C6'>" + 5 + "M</span>哦！<br>换个小点的文件吧！");
    } else if(type == "Q_TYPE_DENIED") {
        alert('请选择mp3后缀的音乐格式');
    }

});


