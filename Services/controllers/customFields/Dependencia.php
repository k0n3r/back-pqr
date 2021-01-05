<?php

namespace App\Bundles\pqr\Services\controllers\customFields;

class Dependencia extends Autocomplete
{
  public function jsContent(): ?string
  {
    return <<<JAVASCRIPT
          let options_{$this->CamposFormato->nombre} = {
            language: "es",
            placeholder: "Ingrese el nombre",
            multiple: false,
            ajax: {
              delay: 400,
              url: baseUrl+`app/modules/back_pqr/app/request.php`,
              dataType: "json",
              data: function(p) {
                var query = {
                  key: localStorage.getItem("key"),
                  token: localStorage.getItem("token"),
                  class: "RequestProcessorController",
                  method: "getListForField",
                  data: {
                    name: '{$this->CamposFormato->nombre}',
                    term: p.term
                  }
                };
                return query;
              }
            }
          };
          $('#{$this->CamposFormato->nombre}').select2(options_{$this->CamposFormato->nombre});
  JAVASCRIPT;
  }
}
