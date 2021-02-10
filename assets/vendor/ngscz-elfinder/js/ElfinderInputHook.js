var ElFinderInputHook = function (input, uniqueId) {

    this.input = input;
    this.uniqueId = uniqueId;
    this.files = [];
    this.locales = [];
    this.fields = [];

    this.initialize = function () {
        this.createAddBtn();
        var values = this.input.getAttribute('data-value');
        if (values) {
            this.files = JSON.parse(this.input.getAttribute('data-value'));
        }
        this.locales = JSON.parse(this.input.getAttribute('data-locales'));
        this.fields = JSON.parse(this.input.getAttribute('data-fields'));
        this.refresh();
    };

    this.createAddBtn = function () {
        var self = this;

        var a = document.createElement('a');
        a.className = 'btn btn-primary';
        a.href = '';

        var linkIcon = document.createElement('i');
        linkIcon.className = 'fa fa-files-o';
        a.appendChild(linkIcon);

        var linkText = document.createTextNode(' Vyberte soubory ');
        a.appendChild(linkText);

        a.onclick = function (e) {
            e.preventDefault();
            window.open('/elfinder/default?fileCallback=setValue_' + uniqueId + '&onlyMimes=' + self.onlyMimes() + '&isMultiple=' + self.isMultiple(), 'popupWindow', 'height=450, width=900');
        };

        this.input.parentNode.appendChild(a);
    };

    this.setValue = function (file) {
        if (!this.isMultiple()) {
            this.files = [];
        }
        this.addFile(file);
    };

    this.refresh = function () {
        this.refreshInputValues();
        this.refreshList();
    };

    this.onlyMimes = function () {
        var onlyMimes = this.input.getAttribute('data-only-mimes');
        return onlyMimes;
    };

    this.isMultiple = function() {
        var multiple = this.input.getAttribute('data-multiple');
        return parseInt(multiple);
    }

    this.isImage = function(url) {
        var re = /(?:\.([^.]+))?$/;
        var ext = re.exec(url)[1];

        var imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if(imageExtensions.indexOf(ext.toLowerCase()) != -1) {
            return true;
        }
        return false;
    }

    this.refreshList = function () {

        var self = this;
        var elFinderList = this.input.parentNode.querySelector('.elfinder-list');

        if (!elFinderList) {
            elFinderList = document.createElement('div');
            elFinderList.className = 'elfinder-list';
            this.input.parentNode.appendChild(elFinderList);
        }

        elFinderList.innerHTML = '';

        var row = document.createElement('div');
        row.className = 'row';

        this.files.forEach(function (item, i){
            var col = document.createElement('div');
            col.className = (self.isMultiple()) ? 'col-xs-6 col-sm-4 col-sortable' : 'col-xs-12';

            var thumbnail = document.createElement('div');
            thumbnail.className = 'thumbnail';
            thumbnail.style.textAlign = 'center';

            if (self.isImage(item.url)) {
                var img = document.createElement('img');
                img.src = item.url;
            } else {
                var img = document.createElement('i');
                img.className = 'fa fa-file fa-5x';
            }
            //img.width = 100;
            //img.height = 100;

            thumbnail.appendChild(img);

            var caption = document.createElement('div');
            caption.className = 'caption';

            var removeBtn = document.createElement('button');
            var removeBtnIcon = document.createElement('i');
            removeBtnIcon.className = 'fa fa-trash-o';
            var removeBtnText = document.createTextNode(' Smazat');

            removeBtn.className = 'btn btn-block red';
            removeBtn.href = '';
            removeBtn.setAttribute('data-hash', item.hash);
            removeBtn.appendChild(removeBtnIcon);
            removeBtn.appendChild(removeBtnText);

            removeBtn.onclick = function (e) {
                e.preventDefault();
                var a = e.currentTarget;
                self.removeFileByHash(a.getAttribute('data-hash'));
                self.refresh();

            };

            caption.appendChild(removeBtn);

            var languageBlock = document.createElement('div');
            languageBlock.setAttribute('class', 'tabbable-custom');

            var languageNav = document.createElement('ul');
            languageNav.setAttribute('class', 'nav nav-tabs');

            var languageTab = document.createElement('div');
            languageTab.setAttribute('class', 'tab-content');

            self.locales.forEach(function (language) {
                var languageLi = document.createElement('li');
                if (language.locale === 'cs') {
                    languageLi.setAttribute('class', 'active');
                }
                var languageLink = document.createElement('a');
                languageLink.setAttribute('href', '#lang_' + language.locale + '_' + item.hash);
                languageLink.setAttribute('data-toggle', 'tab');
                languageLink.setAttribute('data-expanded', 'false');
                languageLink.appendChild(document.createTextNode(language.label));
                languageLi.appendChild(languageLink);

                languageNav.appendChild(languageLi);

                var languageTabContent = document.createElement('div');
                languageTabContent.setAttribute('class', ((language.locale === 'cs') ? 'active' : '') + ' tab-pane');
                languageTabContent.setAttribute('id', 'lang_' + language.locale + '_' + item.hash);

                self.fields.forEach(function(field) {
                    var label = document.createElement('label');
                    label.setAttribute('style', 'display: block; text-align: left; color: #73879C;');
                    label.appendChild(document.createTextNode(field.label));
                    languageTabContent.appendChild(label);

                    var input = null;
                    var inputValue = (typeof item[language.locale] !== 'undefined') ? (item[language.locale][field.name] || '') : '';

                    if (field.type === 'text') {
                        input = document.createElement('input');
                        input.setAttribute('type', 'text');
                        input.setAttribute('value', inputValue);

                    } else if (field.type === 'textarea') {
                        input = document.createElement('textarea');
                        input.value = inputValue;
                    } else {
                        throw 'Unknown field type: ' + field.type;
                    }

                    input.setAttribute('name', field.name);
                    input.setAttribute('style', 'width: 100%');
                    input.setAttribute('data-hash', item.hash);
                    input.setAttribute('data-locale', language.locale);
                    input.addEventListener('input', function (e) {
                        self.onInputChange(e);
                    });

                    languageTabContent.appendChild(input);
                });

                languageTab.appendChild(languageTabContent);
            });

            languageBlock.appendChild(languageNav);
            languageBlock.appendChild(languageTab);

            caption.appendChild(languageBlock);

            thumbnail.appendChild(caption);
            col.appendChild(thumbnail);

            row.appendChild(col);
        });

        elFinderList.appendChild(row);

        if (typeof Sortable === 'function') {
            var self = this;
            Sortable.create(row, {
                handle: '.col-sortable',
                onEnd: function (/**Event*/evt) {
                    var temp = self.files[evt.newIndex];
                    self.files[evt.newIndex] = self.files[evt.oldIndex];
                    self.files.splice(evt.oldIndex, 1);

                    var newFiles = [];
                    var add = false;
                    self.files.forEach(function (item, i) {
                        newFiles.push(item);
                        if (i == evt.newIndex) {
                            add = true;
                            newFiles.push(temp);
                        }
                    });
                    if (!add) {
                        newFiles.push(temp);
                    }

                    self.files = newFiles;

                    self.refreshInputValues();
                },
            });
        }
    };

    this.onInputChange = function (e) {
        var self = this;
        var $input = $(e.target);
        self.files.forEach(function (item, i){
            if (item.hash === $input.data('hash')) {
                if (typeof self.files[i][$input.data('locale')] === 'undefined') {
                    self.files[i][$input.data('locale')] = {};
                }
                self.files[i][$input.data('locale')][$input.attr('name')] = $input.val();
            }
        });
        self.refreshInputValues();
    };

    this.refreshInputValues = function () {
        this.input.value = JSON.stringify(this.files);
    };

    this.addFile = function (file) {
        var hasFile = false;
        this.files.forEach(function (item, i) {
            if (item.hash == file.hash) {
                hasFile = true;
            }
        });

        if (!hasFile) {
            this.files.push(file);
        }
        this.refresh();
    };

    this.removeFileByHash = function (hash) {
        var indexToRemove = null;
        this.files.forEach(function (item, i) {
            if (item.hash == hash) {
                indexToRemove = i;
            }
        });

        if (indexToRemove != null) {
            this.files.splice(indexToRemove, 1);
        }
        this.refresh();
    };

    this.initialize();
};

document.addEventListener("DOMContentLoaded", function (event) {
    var inputs = document.querySelectorAll('[data-elfinder]');

    inputs.forEach(function (item, i) {
        window['elFinderHook_' + i] = new ElFinderInputHook(item, i);
        window['setValue_' + i] = function (file) {
            window['elFinderHook_' + i].setValue(file);
        }
    });
});
