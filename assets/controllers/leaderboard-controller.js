import {Controller} from '@hotwired/stimulus';
import 'jquery'

export default class extends Controller {
    static targets = ['img', 'background']
    connect() {
    }

    disconnect() {
    }

    back() {
        // history.back();// Swup instance
        //  return false;
    }
}