/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

jQuery(function($){

    $(window).on('load', function (e) {
        $('.courseProgressByBlock').each(updateSelectionProgress);
    });

    $('.courseChoice[type=checkbox]').change(updateSelectionProgress);

    function updateSelectionProgress() {
        var blockID = $(this).data('block');

        updateBlockProgress(blockID);

        updateOfferingProgress();
    }

    function updateBlockProgress(blockID) {
        var choices = $('#selectionChoices').find('.courseBlock'+blockID);

        var choicesSelected = $('#selectionChoices').find('.courseBlock'+blockID+':checked');

        var progressDiv = $('.courseProgressByBlock[data-block="'+blockID+'"]');
        var progressInfo = '';

        var min = progressDiv.data('min');
        var max = progressDiv.data('max');

        if (choicesSelected.length >= min) {
            //progressDiv.html('Okay');
            progressDiv.addClass('complete');
            progressDiv.find('.invalid').hide();
            progressDiv.find('.valid').show();

            if (max > min && choicesSelected.length < max) progressInfo += 'Select up to '+max;
            else progressInfo += 'Complete';

            progressDiv.find('.valid').prop('title', progressInfo);
        } else {
            //progressDiv.html('Select '+(min - choicesSelected.length));
            progressDiv.removeClass('complete');
            progressDiv.find('.valid').hide();
            progressDiv.find('.invalid').show();

            if (min > 0) progressInfo += 'Select '+(min - choicesSelected.length);
            if (max > min) progressInfo += ' (up to '+max+')';
            progressDiv.find('.invalid').prop('title', progressInfo);
        }



        if (choicesSelected.length >= max) {
            choices.each(function() {
                $(this).data('full', true);
                if ($(this).prop('checked') == false) {
                    $(this).prop('disabled', true);
                }
            });
        } else {
            choices.each(function() {
                $(this).data('full', false);
                $(this).prop('disabled', false);
            });
        }
    }

    function updateOfferingProgress() {
        var progressDiv = $('.courseProgressByOffering');

        var min = progressDiv.data('min');
        var max = progressDiv.data('max');

        var choices = $('#selectionChoices').find('.courseChoice');
        var choicesSelected = $('#selectionChoices').find('.courseChoice:checked');

        var blocks = $('#selectionChoices').find('.courseProgressByBlock');
        var blocksComplete = $('#selectionChoices').find('.courseProgressByBlock.complete');

        if (choicesSelected.length >= min) {
            progressDiv.find('.continue').hide();

            if (blocksComplete.length >= blocks.length) {
                progressDiv.find('.invalid').hide();
                progressDiv.find('.valid').show();
            } else {
                progressDiv.find('.valid').hide();
                progressDiv.find('.invalid').show();
            }
        } else {
            progressDiv.find('.valid').hide();
            progressDiv.find('.invalid').hide();
            progressDiv.find('.continue').show();
        }

        if (choicesSelected.length >= max) {
            choices.each(function() {
                if ($(this).prop('checked') == false) {
                    $(this).prop('disabled', true);
                }
            });
        } else {
            choices.each(function() {
                if ($(this).data('full') == false) {
                    $(this).prop('disabled', false);
                }
            });
        }

        //progressDiv.html(text);
        progressDiv.find('.complete').css('width', ( Math.min(choicesSelected.length / min, 1.0)*100)+"%" );
    }
});
