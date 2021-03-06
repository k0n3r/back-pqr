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
              url: baseUrl+`api/pqr/components/autocomplete/list`,
              dataType: "json",
              data: function(p) {
                var query = {
                  key:  localStorage.getItem("key"),
                  token: localStorage.getItem("token"),
                  name: '{$this->CamposFormato->nombre}',
                  data: {
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
