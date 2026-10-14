<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * People's names cannot have numbers (or symbols like @ # $), on EVERY form of the system:
 * register, profiles, customers, users, walk-in booking and the signer of a waiver.
 * Allowed: letters (also ñ and accents), spaces, periods (Ma.), hyphens (Mary-Ann) and apostrophes (O'Neil).
 *
 *  - On the server: a sent form with a wrong name goes back with an error under the field.
 *  - In the browser: numbers and symbols are removed while typing, with a short note under the field.
 * Pet names are not included (a pet may be called "K9").
 */
class PersonNamesOnly
{
    // Form field => what the error message calls it
    public const FIELDS = [
        'first_name' => 'first name',
        'firstname' => 'first name',
        'last_name' => 'last name',
        'lastname' => 'last name',
        'middle_name' => 'middle name',
        'middlename' => 'middle name',
        'signer_name' => 'name of the signer',
    ];

    // Letters (any language), spaces, period, hyphen, apostrophe
    public const PATTERN = "/^[\\p{L}\\p{M}\\s.'\\-]*$/u";

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            $errors = [];
            foreach (self::FIELDS as $field => $label) {
                $value = $request->input($field);
                if (is_string($value) && ! preg_match(self::PATTERN, $value)) {
                    $errors[$field] = 'The ' . $label . ' can only have letters, spaces, periods (.), hyphens (-) and apostrophes (\'). Numbers are not allowed.';
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        $response = $next($request);

        // Pages with a name field get the small script that blocks numbers while typing
        $type = (string) $response->headers->get('Content-Type');
        $content = $response->getContent();
        if (str_contains($type, 'text/html') && is_string($content) && str_contains($content, '</body>')
            && preg_match('/name="(' . implode('|', array_keys(self::FIELDS)) . ')"/', $content)) {
            // keep the view object: Laravel features (and the tests) still look at it
            $original = $response instanceof \Illuminate\Http\Response ? $response->getOriginalContent() : null;
            $response->setContent(substr_replace($content, self::SCRIPT . '</body>', strrpos($content, '</body>'), 7));
            if ($original !== null) {
                $response->original = $original;
            }
        }

        return $response;
    }

    private const SCRIPT = <<<'HTML'
<script>
  // Names: letters only. Numbers and symbols are removed while typing (the server checks again).
  (function () {
    var fields = 'input[name=first_name],input[name=firstname],input[name=last_name],input[name=lastname],input[name=middle_name],input[name=middlename],input[name=signer_name]';
    var bad = /[^\p{L}\p{M}\s.'\-]/u;
    var badAll = /[^\p{L}\p{M}\s.'\-]/gu;
    document.querySelectorAll(fields).forEach(function (input) {
      var note = document.createElement('small');
      note.textContent = 'Numbers and symbols are not allowed in names.';
      note.style.cssText = 'display:none;margin-top:4px;color:#c0392b;font-size:12.5px;';
      input.insertAdjacentElement('afterend', note);
      input.addEventListener('input', function () {
        if (bad.test(input.value)) {
          var at = input.selectionStart - (input.value.slice(0, input.selectionStart).match(badAll) || []).length;
          input.value = input.value.replace(badAll, '');
          try { input.setSelectionRange(at, at); } catch (e) {}
          note.style.display = 'block';
          clearTimeout(input._nameNote);
          input._nameNote = setTimeout(function () { note.style.display = 'none'; }, 2500);
        }
      });
    });
  })();
</script>
HTML;
}
