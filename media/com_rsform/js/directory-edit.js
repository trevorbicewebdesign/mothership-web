document.addEventListener('DOMContentLoaded', function(){
    var buttons = document.querySelectorAll('[data-directory-task]');

    if (buttons.length > 0) {
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', function(){
                directorySave(this.getAttribute('data-directory-task'));
            })
        }
    }
});

function directorySave(task) {
    var form = document.getElementById('directoryEditForm');
    form.task.value = task;
    form.submit();
}