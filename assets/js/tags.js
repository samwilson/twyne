import AutoComplete from '@tarekraafat/autocomplete.js/dist/js/autoComplete.js';
import '@tarekraafat/autocomplete.js/dist/css/autoComplete.css';

const elementId = '#wikidata';

// eslint-disable-next-line no-new
new AutoComplete({
    selector: elementId + '-label',
    debounce: 500,
    maxResults: 20,
    data: {
        src: async () => {
            const input = document.querySelector(elementId + '-label');
            input.disabled = true;
            const source = await fetch(`/wikidata.json?q=${input.value}`);
            const data = await source.json();
            input.disabled = false;
            return data;
        },
        key: ['title']
    },
    resultItem: {
        content: (data, element) => {
            element.innerHTML = `
                <a href="https://www.wikidata.org/wiki/${data.value.value}">${data.value.value}</a>:
                <strong>${data.value.title}</strong> &mdash;
                <dfn>${data.value.description}</dfn>`;
        }
    },
    onSelection: feedback => {
        document.querySelector(elementId).value = feedback.selection.value.value;
        document.querySelector(elementId + '-label').value = feedback.selection.value.title;
    }
});
