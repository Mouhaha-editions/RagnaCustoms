/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './bootstrap';
// window.$ = window.jQuery = $;
// import 'select2/dist/js/select2.full.min';
// require('../public/bundles/tetranzselect2entity/js/select2entity');
//
// $(document).on('click', 'form .rating', function () {
//
//     let t = $(this);
//     let ratingItems = $(this).parent().find('.rating');
//
//     let getStarsRating = function () {
//         let rating = 0;
//         for (let ratingItem of ratingItems) {
//             if ($(ratingItem).find("i").hasClass("fas")) {
//                 rating++;
//             }
//         }
//         return rating;
//     };
//
//     let setStarsRating = function () {
//         t.parent().data('rating', getStarsRating());
//         t.parent().trigger('change');
//     };
//
//     const fillStar = function (item) {
//         $(item).find("i").removeClass("far")
//         $(item).find("i").addClass("fas")
//     };
//
//     const emptyStar = function (item) {
//         $(item).find("i").removeClass("fas")
//         $(item).find("i").addClass("far")
//     };
//
//     const toggleStars = function () {
//         const itemIndex = t.data('id');
//         for (let i = 0; i <= itemIndex; i++) {
//             fillStar(ratingItems[i]);
//         }
//
//         for (let i = itemIndex + 1; i < ratingItems.length; i++) {
//             emptyStar(ratingItems[i]);
//         }
//         setStarsRating();
//     };
//     toggleStars();
// });
//
//
// $(function () {
//     $('.select2entity[data-autostart="true"]').select2entity();
//     $('.select2').select2();
//
//
//
//     let seasonEnd = $("#variables").data('season-ends-at');
//     var countDownDate = new Date(seasonEnd).getTime();
//
// // Update the count down every 1 second
//     var x = setInterval(function () {
//
//         // Get today's date and time
//         var now = new Date().getTime();
//
//         // Find the distance between now and the count down date
//         var distance = countDownDate - now;
//
//         // Time calculations for days, hours, minutes and seconds
//         var days = Math.floor(distance / (1000 * 60 * 60 * 24));
//         var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
//         var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
//         var seconds = Math.floor((distance % (1000 * 60)) / 1000);
//
//         // Display the result in the element with id="demo"
//         $("#demo").html(days + "d " + hours + "h "
//             + minutes + "m " + seconds + "s ");
//
//         // If the count down is finished, write some text
//         if (distance < 0) {
//             clearInterval(x);
//             $("#demo").html("EXPIRED");
//         }
//     }, 1000);
// })
//
// const copyToClipboard = str => {
//     const el = document.createElement('textarea');
//     el.value = str;
//     el.setAttribute('readonly', '');
//     el.style.position = 'absolute';
//     el.style.left = '-9999px';
//     document.body.appendChild(el);
//     el.select();
//     document.execCommand('copy');
//     document.body.removeChild(el);
// };
//
//
// let hashtag = window.location.hash;
//
// function loadForm(content) {
//
//     $("#form-edit").html(content);
//
//     $("#form-edit form").on('submit', function () {
//         $("#form-edit").html("<div class=\"popup-box-actions white full void\">Sending your form, please wait ... </div> " +
//             "<div class='progress-container'><div class='progress'></div></div>");
//         let tt = $(this);
//
//         $.ajax({
//             xhr: function () {
//                 let xhr = new window.XMLHttpRequest();
//                 xhr.upload.addEventListener("progress", function (evt) {
//                     if (evt.lengthComputable) {
//                         let percentComplete = evt.loaded / evt.total;
//                         $('.progress').css({
//                             width: percentComplete * 100 + '%'
//                         });
//                         if (percentComplete === 1) {
//                             $('.progress').addClass('hide');
//                         }
//                     }
//                 }, false);
//                 xhr.addEventListener("progress", function (evt) {
//                     if (evt.lengthComputable) {
//                         let percentComplete = evt.loaded / evt.total;
//                         $('.progress').css({
//                             width: percentComplete * 100 + '%'
//                         });
//                     }
//                 }, false);
//                 return xhr;
//             },
//             url: tt.attr('action'),
//             data: new FormData(this),
//             type: tt.attr('method'),
//             processData: false,
//             contentType: false,
//             success: function (data) {
//                 if (data.reload) {
//                     window.location.reload();
//                 }
//                 if (data.error === true || data.success === false) {
//                     $("#form-edit").html(data.response);
//                     loadForm(data.response);
//                     $('.select2entity').select2entity();
//
//                 } else {
//                     tt.closest(tt.data('replace-selector')).html(data.response);
//                     $(tt).closest(".modal").modal('hide');
//                 }
//             }
//         });
//
//
//         $("#form-review").html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
//         return false;
//     });
// }
//
// $(function () {
//
//
//
//
//     // $(".popover-trigger").popover({trigger:'mouseover'});
//     // $(".popover-trigger").popover("show");
//     $(document).on('mouseover', ".popover-trigger", function () {
//         $(this).popover("show");
//     });
//     // $(document).on('mouseout', ".popover-trigger", function () {
//     //     $(this).popover("hide");
//     // });
//     $(document).on('change', '#review-global', function () {
//         let rating = parseInt($(this).data('rating'));
//         $('.rating-list.text-warning').each(function () {
//             let t = $(this).find(".rating:eq(" + (rating - 1) + ")").click();
//         });
//     });
//
//
//     $(document).on("click", ".ajax-link", function () {
//         let t = $(this);
//         let action = t.data('success-action');
//         $.ajax({
//             url: t.data('url'),
//             dataType: 'json',
//             success: function (data) {
//                 if (data.error) {
//                     alert(data.errorMessage);
//                     return;
//                 }
//                 switch (action) {
//                     case "replace":
//                         console.log('replace'+t.data('replace-selector'))
//                         console.log(data.result)
//                         $(t.data('replace-selector')).replaceWith(data.result);
//                         break;
//                     case "remove":
//                         $(t.data('remove-selector')).remove();
//                         break;
//                 }
//             },
//             error: function (data) {
//                 alert('Erreur lors de la requete');
//             }
//
//         });
//
//         return false;
//     });
//
//     $(document).on('click', '.ask-for-confirmation', function () {
//         return confirm("Your are going to delete an element definitely, do you confirm ?");
//     });
//     // $("#leaderboard-title select").select2();
//
//     $(document).on('click', ".ajax-load", function () {
//         let t = $(this);
//         let body = $(t.data('target') + " .modal-body");
//         body.html("loading ...");
//         let target = t.data('target');
//         $.ajax({
//             url: t.data('url'),
//             data: {
//                 id: t.data('song-id')
//             },
//             success: function (data) {
//                 body.html(data.response);
//                 $(".rating-list").on('change', function () {
//                     let t = $(this);
//                     $('input[name="' + t.data('input-selector') + '"]').val(t.data('rating'));
//                 });
//                 body.find("form").on('submit', function () {
//                     let test = true;
//                     $(this).find('input').each(function () {
//                         if ($(this).is(".verify-voted") && ($(this).val() === undefined || $(this).val() === "")) {
//                             test = false;
//                         }
//                     });
//                     if (!test) {
//                         alert("you need to rate each property");
//                         return false;
//                     }
//
//                     let tt = $(this);
//                     $.ajax({
//                         url: tt.data('url'),
//                         type: tt.attr('method'),
//                         data: tt.serialize(),
//                         success: function (data) {
//                             if (t.data('refresh')) {
//                                 window.location.reload();
//                             }
//                             t.closest(t.data('replace-closest-selector')).html(data.response);
//                             $(t.data('replace-selector')).html(data.response);
//                             $(".modal:visible").modal('hide');
//                         }
//                     });
//
//                     body.html("<div class=\"popup-box-actions white full void\">Sending your form</div>");
//
//
//                     return false;
//                 });
//             }
//         });
//         return false;
//     });
//
//     $(document).on('click', ".ajax-modal-form", function () {
//         let t = $(this);
//
//         $(t.data('modal')).modal('show');
//         $.ajax({
//             url: t.attr('href'),
//             success: function (data) {
//                 console.log("load form");
//                 loadForm(data.response);
//                 console.log("enable select2");
//
//                 $('.select2entity').select2entity();
//             }
//         });
//         return false;
//
//     });
//
//     $(document).on('change', "#utilisateur_isPublic", function () {
//         let t = $(this);
//         if (t.is(":checked")) {
//             $("#public_informations").show();
//         } else {
//             $("#public_informations").hide();
//         }
//     });
//
//     $("#utilisateur_isPublic").trigger('change');
//     $(document).on('change', "#chkSwitch", function () {
//         let body = $('body');
//         if ($(this).is(':checked')) {
//             body.removeClass('light');
//             body.addClass('dark');
//             setCookie("light-mode", "dark")
//         } else {
//             body.addClass('light');
//             body.removeClass('dark');
//             setCookie("light-mode", "light")
//         }
//     });
//
//     if (getCookie("light-mode") === null) {
//         let chkSwitch = $('#chkSwitch');
//         if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
//             chkSwitch.attr('checked', 'checked');
//             chkSwitch.trigger('change');
//         }
//     }
//
//     // $(".popover-trigger").popover({ trigger: "hover" });
//     $("#form_replaceExisting").on('change', function () {
//         let t = $(this);
//         if (t.is(':checked')) {
//             $("#form_resetVote").closest("div").removeClass('d-none');
//         } else {
//             $("#form_resetVote").closest("div").addClass('d-none');
//         }
//     });
//     $(".nav-tabs > .nav-item:first-child>a").click();
//     if (window.location.pathname !== "/leaderboard") {
//         $("[data-target='" + hashtag + "']").click();
//         $(".nav a").on('click', function () {
//             window.location.hash = $(this).data("target");
//         });
//     }
//     // createToast("my title", "some content", 12);
// });
//
//
// function setCookie(cname, cvalue) {
//     var d = new Date();
//     d.setTime(d.getTime() + (5000 * 24 * 60 * 60 * 1000));
//     var expires = "expires=" + d.toUTCString();
//     document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
// }
//
//
// function getCookie(cname) {
//     var name = cname + "=";
//     var decodedCookie = decodeURIComponent(document.cookie);
//     var ca = decodedCookie.split(';');
//     for (var i = 0; i < ca.length; i++) {
//         var c = ca[i];
//         while (c.charAt(0) == ' ') {
//             c = c.substring(1);
//         }
//         if (c.indexOf(name) == 0) {
//             return c.substring(name.length, c.length);
//         }
//     }
//     return null;
// }
//
//
// $(document).on('change','#add_playlist_form_playlist',function(){
//     let t = $(this);
//     if(t.val() !== undefined && t.val().trim() !== "" && t.val() !== null){
//         $("#add_playlist_form_newPlaylist").parent().hide();
//         $("#add_playlist_form_newPlaylist").removeAttr("required");
//     }else{
//         $("#add_playlist_form_newPlaylist").parent().show();
//         $("#add_playlist_form_newPlaylist").attr("required","required");
//     }
// })
