import updateWhiteListIpAddresses from './update-whitelist-ip-addresses';
import updateSwitchAndItsLabel from './update-switch-label';

export default class ConfirmIdentity{
    /**
     * @param {string} controller_url
     * @param {HTMLElement} element_to_listen
     * @param {object} fetch_options
     * @param {string} type_of_event
     */

    constructor({controller_url, element_to_listen, fetch_options, type_of_event}) {
        this.confirm_identity_close_modal_button = document.body.querySelector('button[id="close_confirm_identity_password_modal"]');
        this.confirm_identity_display_modal_button = document.body.querySelector('button[data-target="confirm_identity_password_modal"]');
        this.confirm_identity_modal = document.body.querySelector('div[id="confirm_identity_password_modal"]');
        this.confirm_identity_modal_body = document.body.querySelector('div[id="confirm_identity_password_modal_body"]');
        this.controller_url = controller_url;
        this.element_to_listen = element_to_listen;
        this.fetch_options = fetch_options;
        this.type_of_event = type_of_event;
        this.init();
    }

    init(){
        this.element_to_listen.addEventListener(this.type_of_event, (event) => this.callServerToDisplayConfirmModal(event));
    }
    async callServerToDisplayConfirmModal(event) {
        if (this.controller_url === "/user/account/profile/toggle-checking-ip"){
            this.fetch_options.body = document.body.querySelector('input[id="check_user_ip_checkbox"]').checked;
        }

        if (this.controller_url === "/users/account/profile/edit-user-ip-whitelist"){
            const user_ip_entered_array = checkUserIpEntered(event);

            if (!user_ip_entered_array){
                return;
            }
            this.fetch_options.body = user_ip_entered_array;
        }
        if (this.controller_url === "/user/account/profile/modify-password"){
            event.preventDefault();

        }
        try{
            const response = await fetch(this.controller_url, this.fetch_options);
            const {is_password_confirmed} = await response.json();
            !is_password_confirmed ? this.displayConfirmIdentityModal(): null;
        } catch (error){
            console.error(error);
        }
    }
    displayConfirmIdentityModal(){
        this.createConfirmPasswordForm();

        this.confirm_identity_modal.addEventListener('show.bs.modal', () => {
            this.confirm_identity_password_input.focus();
        });
        this.confirm_identity_display_modal_button.click();

        this.confirm_identity_modal_form.addEventListener('submit', (event) => this.confirmIdentity(event));

        this.confirm_identity_modal.addEventListener('hidden.bs.modal', () => {
            const checkbox_label = document.body.querySelector('label[for="check_user_ip_checkbox"]').textContent;

            document.body.querySelector('input[id="check_user_ip_checkbox"]').checked = checkbox_label === "Active" ? true : false;
        });
    }
    createConfirmPasswordForm() {
        if (document.body.querySelector('form[id="confirm_identity_form"]')){
            document.body.querySelector('form[id="confirm_identity_form"]').remove();
        }

        const form_element = document.createElement('form');
        form_element.id = "confirm_identity_form";
        form_element.method = "POST";

        const fieldset_element = document.createElement('fieldset');

        const label_element = document.createElement('label');
        label_element.htmlFor = "Confirm_identity_password_input";
        label_element.textContent = "Password: ";

        const input_element = document.createElement('input');
        input_element.type = "password";
        input_element.name = "confirm_identity_password";
        input_element.id = "confirm_identity_password_input";
        input_element.class = "form-control";

        const paragraph_element = document.createElement('p');
        paragraph_element.id = "invalid_password_entered";
        paragraph_element.className = "text-danger d-none mt-3";
        paragraph_element.innerHTML = "&#x26D4; Password invalid. &#x26D4;";

        const button_element = document.createElement('button');
        button_element.type = "submit";
        button_element.className = "btn btn-success mt-3";
        button_element.textContent = "Confirm";

        fieldset_element.append(label_element, input_element, paragraph_element);

        form_element.append(fieldset_element, button_element);

        this.confirm_identity_modal_body.append(form_element);

        this.confirm_identity_modal_form = form_element;

        this.confirm_identity_password_input = input_element;

        this.confirm_identity_modal_invalid_paragraph = paragraph_element;
    }

    createConfirmPasswordForm(){

    }
    async confirmIdentity(event){
        event.preventDefault();

        const password = this.confirm_identity_password_input.value;

        const fetch_options = {
            body: JSON.stringify({password}),
            headers: {
                'Accept' : 'application/json',
                'Content_type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Confirm-Identity-With-Password': 'true'
            },
            method: 'POST'
        };
        try{
            const response = await fetch(this.controller_url, fetch_options);
            const {is_guard_checking_IP, is_password_confirmed, login_route, status_code, user_IP} = await response.json();
            this.resetPasswordInput();
            if (status_code === 302){
                window.location.href = login_route;
            }
            if (is_password_confirmed){
                this.passwordIsConfirmed(this.controller_url, is_guard_checking_IP, user_IP);
            }else {
                this.passwordIsInvalid();
            }
        }catch (error) {
            console.error(error);
        }
    }
    resetPasswordInput(){
        this.confirm_identity_password_input.value = "";
        this.confirm_identity_password_input.focus();
    }
    passwordIsConfirmed(controller_url, is_guard_checking_IP, user_IP){
        switch (controller_url) {
            case '/user/account/profile/add-current-IP': updateWhiteListIpAddresses(user_IP); break;
            case '/user/account/profile/edit-user-ip-whitelist': updateWhiteListIpAddresses(user_IP); break;
            case '/user/account/profile/toggle-checking-ip': updateSwitchAndItsLabel(is_guard_checking_IP); break;
        }
        this.confirm_identity_close_modal_button.click();
    }
    passwordIsInvalid(){
        this.confirm_identity_modal_invalid_paragraph.classList.remove('d-none');
        setTimeout(() => this.confirm_identity_modal_invalid_paragraph.classList.add('d-none'), 2000);
    }
}