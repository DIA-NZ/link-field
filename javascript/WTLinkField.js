(function ($) {
        $.entwine('ss', function ($) {
                $('.wtlink input[type=radio]').entwine({
                        //onmatch: function() {
                    onadd: function () {
                            var self = this;
                        if (self.is(':checked')) {
                                self.toggle();
                        }
                            this._super();
                    },
                    onremove: function () {
                            this._super();
                    },
                    onclick: function () {
                            this.toggle();
                    },
                    toggle: function () {
                            var val = $(this).attr('value');
                            this.closest('.wtlink').find('.fieldgroupField .composite .form__fieldgroup').children().each(function () {
                                    var arrToMatch = $(this).attr('id').match(/_(.*)_Holder$/u);
                                if (arrToMatch.length > 1 && arrToMatch[1].match(val)) {
                                        $(this).show();
                                } else if (!$(this).hasClass('no-hide')) {
                                        $(this).hide();
                                }
                            });
                    }
                });

                $('.wtlink').entwine({
                        //onmatch:function() {
                    onadd: function () {
                            var self = this;
                            setTimeout(function () {
                                if (!self.find('input:checked').length) {
                                        self.find('input[type=radio]').first().attr('checked', 'checked').toggle();
                                }
                            }, 10);

                            this._super();
                    },
                    onremove:function () {
                            this._super();
                    }
                });
        });
})(jQuery);