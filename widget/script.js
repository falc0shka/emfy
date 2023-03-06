define(["jquery", "lib/components/base/modal"], function ($, Modal) {
  "use strict";

  return function () {
    var self = this;

    this.callbacks = {
      render: function () {
        self.render_template(
          {
            body: "",
            render: self.render(
              { ref: "/tmpl/controls/button.twig" },
              {
                class_name: "products_summary_button",
                name: "products_summary_button",
                text: "Товары",
                blue: true,
              }
            ),
          },
          {}
        );

        return true;
      },

      init: function () {
        return true;
      },

      bind_actions: function () {
        var $button = $(".products_summary_button");
        $button.on("click", function () {
          $button.trigger("button:load:start");
          var leadId = window.location.href.substring(window.location.href.lastIndexOf('/') + 1);
          console.log(leadId);
          $.get(`https://89.175.21.158:49001/${leadId}`, function (data) {
            var modal = new Modal({
              class_name: "products-modal-window",
              init: function ($modal_body) {
                var $this = $(this);
                $modal_body
                  .trigger("modal:loaded") // запускает отображение модального окна
                  .html(data)
                  .trigger("modal:centrify") // настраивает модальное окно
                  .append("");
              },
              destroy: function () {},
            });
          });
          $button.trigger("button:load:stop");

        });

        return true;
      },

      settings: function () {
        return true;
      },

      onSave: function () {
        return true;
      },
    };
  };
});
