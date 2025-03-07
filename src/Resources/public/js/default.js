// function to disable the related input field
function disableRelatedInput(relatedInput) {
  relatedInput.disabled = 'disabled';
  relatedInput.parentNode.classList.add('disabled-input');

  // if input has "data-file-type" attribute, get parent "monsieurbiz-sylius-file-manager__field" element, find last "field" element and add disabled class
  if (relatedInput.dataset.fileType) {
    const buttonContainer = relatedInput.closest('.monsieurbiz-sylius-file-manager__field').lastElementChild;
    if (buttonContainer) {
      buttonContainer.classList.add('disabled-input');
    }
  }

  // dispatch custom disabled event to custom scripts
  document.dispatchEvent(new CustomEvent('mbiz:config:disabled-field', {
    detail: {
      relatedInput: relatedInput
    }
  }));
}

// function to enable the related input field
function enableRelatedInput(relatedInput) {
  relatedInput.disabled = '';
  relatedInput.parentNode.classList.remove('disabled-input');
  if (relatedInput.dataset.fileType) {
    const buttonContainer = relatedInput.closest('.monsieurbiz-sylius-file-manager__field').lastElementChild;
    if (buttonContainer) {
      buttonContainer.classList.remove('disabled-input');
    }
  }

  // dispatch custom enabled event to custom scripts
  document.dispatchEvent(new CustomEvent('mbiz:config:enabled-field', {
    detail: {
      relatedInput: relatedInput
    }
  }));

  window.setTimeout(function () {
    relatedInput.focus();
  }, 100);
}

(function () {
  document.addEventListener("DOMContentLoaded", function() {
    const components = document.querySelectorAll('[data-component]');
    for (const component of components) {
      switch (component.dataset.component) {
        case 'mbiz-default':
          (function (component) {
            const relatedId = component.dataset.relatedId;
            const relatedInput = document.getElementById(relatedId);

            // Reorganize the two fields
            if (component.dataset.reorganize) {
              var valueField = relatedInput.closest('.field');
              var defaultField = component.closest('.field');
              var fieldsContainer = document.createElement('div');
              var grid = document.createElement('div');

              valueField.parentNode.insertBefore(fieldsContainer, valueField);
              fieldsContainer.appendChild(grid);
              grid.appendChild(valueField);
              grid.appendChild(defaultField);

              fieldsContainer.className = 'field';
              grid.className = 'ui grid';
              valueField.className = 'field twelve wide column';
              defaultField.className = 'field four wide column';
            }

            if (component.checked) {
              disableRelatedInput(relatedInput);
            }
            component.addEventListener('change', function (e) {
              if (!e.target.checked) {
                enableRelatedInput(relatedInput);
              } else {
                disableRelatedInput(relatedInput);
              }
            });
          })(component);
          break;
        default:
      }
    };
  });
})();
