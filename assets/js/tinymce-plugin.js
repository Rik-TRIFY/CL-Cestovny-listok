(function() {
    tinymce.create('tinymce.plugins.CLVariables', {
        init: function(editor, url) {
            editor.addButton('cl_variables', {
                type: 'menubutton',
                text: 'Premenné lístka',
                icon: false,
                menu: [
                    {
                        text: 'Vložiť logo',
                        onclick: function() {
                            editor.insertContent('{logo}');
                        }
                    },
                    {
                        text: 'Dátum',
                        onclick: function() {
                            editor.insertContent('{datum}');
                        }
                    },
                    {
                        text: 'Čas',
                        onclick: function() {
                            editor.insertContent('{cas}');
                        }
                    },
                    {
                        text: 'Číslo lístka',
                        onclick: function() {
                            editor.insertContent('{cislo_listka}');
                        }
                    },
                    {
                        text: 'Predajca',
                        onclick: function() {
                            editor.insertContent('{predajca}');
                        }
                    },
                    {
                        text: 'Položky',
                        onclick: function() {
                            editor.insertContent('{polozky}');
                        }
                    },
                    {
                        text: 'Suma',
                        onclick: function() {
                            editor.insertContent('{suma}');
                        }
                    }
                ]
            });

            // Pridáme tlačidlo pre POS premenné
            editor.addButton('cl_pos_variables', {
                type: 'menubutton',
                text: 'POS Premenné',
                icon: false,
                menu: [
                    {
                        text: 'Hlavička',
                        onclick: function() {
                            editor.insertContent('{pos_header}');
                        }
                    },
                    {
                        text: 'Grid lístkov',
                        onclick: function() {
                            editor.insertContent('{pos_grid}');
                        }
                    },
                    {
                        text: 'Košík',
                        onclick: function() {
                            editor.insertContent('{pos_cart}');
                        }
                    },
                    {
                        text: 'Pätička',
                        onclick: function() {
                            editor.insertContent('{pos_footer}');
                        }
                    }
                ]
            });

            // Aktualizujeme náhľad pri zmene obsahu
            editor.on('Change', function(e) {
                if (document.getElementById('preview-auto-refresh').checked) {
                    aktualizujNahlad();
                }
            });
        },
        
        createControl: function(n, cm) {
            return null;
        },
    });

    tinymce.PluginManager.add('cl_variables', tinymce.plugins.CLVariables);
})();
