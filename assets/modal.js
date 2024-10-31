jQuery(document).ready(function($) {
    var modal = $('#rainbow_secure_welcome_modal');
    modal.show(); // Show the modal on load

    var steps = $('.rainbow_secure_modal_step');
    var currentStep = 0;

    $('#rainbow_secure_next, #rainbow_secure_prev').click(function() {
        steps.eq(currentStep).hide();
        currentStep = (this.id === 'rainbow_secure_next') ? 
                    (currentStep + 1) % steps.length : 
                    (currentStep - 1 + steps.length) % steps.length;
        steps.eq(currentStep).show();
    });

    $('.rainbow_secure_close, #rainbow_secure_welcome_modal').click(function(event) {
        if (event.target === this) {
            modal.hide();
        }
    });
});
