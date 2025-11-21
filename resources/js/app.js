import '../css/app.css';
import './bootstrap';
import 'preline';

document.addEventListener('livewire:navigated', () => {
    window.HSstaticMethods.autoInit();
})