import { Controller } from '@hotwired/stimulus';
import  'jquery'
import Swup from 'swup';
export default class extends Controller {
    connect() {
        const swup = new Swup();

    }

}