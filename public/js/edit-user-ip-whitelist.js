import ConfirmIdentity from 'module/confirm-identity';

new ConfirmIdentity({
    controller_url: document.body.querySelector('p[id="user_ip_addresses"]').getAttribute('data-url'),
    element_to_listen: document.body.querySelector('form[name="reset_password"]'),
    fetch_options: {
        body: null,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Password-Modification': 'true'
        },
        method: 'POST'
    },
    type_of_event: "keydown",
});