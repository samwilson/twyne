var $ = require('jquery');
require('select2/dist/js/select2.min');
require('select2/dist/css/select2.min.css');

var wikidataResultTemplate = function (result) {
    console.log(result);
    if (result.loading) {
        return result.text;
    }
    return $(`
        <a href="https://www.wikidata.org/wiki/${result.id}" target="_blank">${result.id}</a>:
        <strong>${result.text}</strong> &mdash;
        <dfn>${result.description}</dfn>
    `);
};

$('select#tags').select2({
    multiple: true,
    tags: true,
    ajax: {
        url: '/tags.json',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return { q: params.term, page: params.page || 1 };
        }
    },
    minimumInputLength: 1
});

$('select#depicts').select2({
    multiple: true,
    ajax: {
        url: '/wikidata.json',
        dataType: 'json',
        delay: 250
    },
    templateResult: wikidataResultTemplate,
    minimumInputLength: 1
});

$('select#wikidata').select2({
    ajax: {
        url: '/wikidata.json',
        dataType: 'json',
        delay: 250
    },
    templateResult: wikidataResultTemplate,
    minimumInputLength: 1
});
