import CodeMirror from 'codemirror/lib/codemirror.js';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/css/css.js';
import 'codemirror/mode/javascript/javascript.js';

const textarea = document.querySelector('textarea[data-codemirror]');
if (textarea) {
    CodeMirror.fromTextArea(textarea, { mode: textarea.dataset.codemirror });
}
