/**
 * Created by Normann on 22/09/2014.
 */
(function($) {
    "use strict";
    $.entwine('ss', function($) {
        $('#Actions ul li').entwine({
            onclick: function(e) {
                //add active state to the current button
                $('#Actions ul li').removeClass('secured-file-active');
                this.addClass('secured-file-active');
                //$('li.dms-active').append('<span class="arrow"></span>');

                //hide all inner field sections
                var panel = $('#SecuritySettingsGroupField');
                panel.children('.middleColumn').children('div.fieldgroup-field').hide();

                //show the correct group of controls
                panel.find('.'+this.data('panel')).closest('div.fieldgroup-field').show();
            }
        });
    });

    $('#SecuritySettingsGroupField .option-change-datetime .optionset input').entwine({
        onchange: function() {
            var lastVal = this.closest('.option-change-datetime').find('.optionset').find('input').last().val();
            this.closest('.option-change-datetime').find('.datetime').toggle(this.val() === lastVal);
        }
    });

    $('#SecuritySettingsGroupField .option-change-listbox .optionset input').entwine({
        onchange: function() {
            var lastVal = this.closest('.option-change-listbox').find('.optionset').find('input').last().val();
            this.closest('.option-change-listbox').find('.group-listbox').toggle(this.val() === lastVal);
        }
    });

    $('input.time.text').entwine({
        onmatch: function() {
            this._super();
            var pickerOpts = {
                useLocalTimezone: true,
                timeFormat: 'HH:mm'
            };
            this.timepicker(pickerOpts);
        }
    });

    $('#SecuritySettingsGroupField').entwine({
        onadd: function() {
            //do an initial show of the entire panel
            this.show();
            //Add placeholder attribute to date and time fields
            $('#SecuritySettingsGroupField input.date.text').attr('placeholder', 'dd-mm-yyyy');
            $('#SecuritySettingsGroupField input.time.text').attr('placeholder', 'hh:mm');

            $('#SecuritySettingsGroupField input.date.text').closest('.fieldholder-small').addClass('datetime').hide();
            $('#SecuritySettingsGroupField .listbox').closest('.fieldholder-small').addClass('group-listbox').hide();
            // We need to duplicate the above functions to work when Adding documents
            $('#Actions li:first-child').click();

            //set the initial state of the radio button and the associated dropdown hiding
            $('#SecuritySettingsGroupField .optionset input[checked]').change();
        }
    });

}(jQuery));