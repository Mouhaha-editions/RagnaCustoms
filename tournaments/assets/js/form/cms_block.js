require('../../css/form/cms_block.scss');

import 'admin-lte/plugins/summernote/summernote-bs4'
import 'trumbowyg/dist/trumbowyg.min'
import 'trumbowyg/dist/langs/fr.min'
import 'trumbowyg/plugins/table/trumbowyg.table'
import 'trumbowyg/plugins/allowtagsfrompaste/trumbowyg.allowtagsfrompaste'


import icons from "trumbowyg/dist/ui/icons.svg"

$.trumbowyg.svgPath = icons;
$('#productbundle_crmblock_content').trumbowyg({
    semantic: false,
    lang: 'fr',
    autogrow: true,
    autogrowOnEnter: true,
    btnsDef: {
        // Create a new dropdown
        image: {
            dropdown: ['insertImage', 'upload'],
            ico: 'insertImage'
        }
    },
    btns: [
        ['viewHTML'],
        ['formatting'],
        ['strong', 'em', 'del'],
        ['foreColor', 'backColor'],
        ['superscript', 'subscript'],
        ['link'],
        ['image'], // Our fresh created dropdown
        ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
        ['unorderedList', 'orderedList'],
        ['horizontalRule'],
        ['removeformat'],
        ['fullscreen']
    ],
    plugins: {
        upload: {
            serverPath: '/upload/image',
            fileFieldName: 'image',
            urlPropertyName: 'data.link'
        },
        resizimg: {
            minSize: 64,
            step: 16
        }
    }
});

