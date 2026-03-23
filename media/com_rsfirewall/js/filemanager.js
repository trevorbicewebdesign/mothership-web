function addFile(name) {
    if (window.opener) {
        var textbox = window.opener.document.getElementsByName('jform[' + name + ']')[0];

        for (var i=0 ;i<document.getElementsByName('cid[]').length; i++) {
            if (document.getElementsByName('cid[]')[i].checked) {
                var file = document.getElementsByName('cid[]')[i].value;

                if (textbox.value.length > 0) {
                    textbox.value += '\n' + file;
                } else  {
                    textbox.value = file;
                }
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var buttons, i;

    buttons = document.querySelectorAll('.com-rsfirewall-add-file');
    for (i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function(){
            addFile(this.getAttribute('data-name'));
        });
    }

    buttons = document.querySelectorAll('.com-rsfirewall-window-close');
    for (i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function(){
            window.close();
        });
    }
});