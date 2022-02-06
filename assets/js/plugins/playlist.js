$(document).on('change','#add_playlist_form_playlist',function(){
    let t = $(this);
    if(t.val() !== undefined && t.val().trim() !== "" && t.val() !== null){
        $("#add_playlist_form_newPlaylist").parent().hide();
        $("#add_playlist_form_newPlaylist").removeAttr("required");
    }else{
        $("#add_playlist_form_newPlaylist").parent().show();
        $("#add_playlist_form_newPlaylist").attr("required","required");
    }
});
