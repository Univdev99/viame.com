#!/bin/sh

find ./Zend -name '*.php' -not -wholename '*/Loader/Autoloader.php' -not -wholename '*/Application.php' -print0 | xargs -0 sed -i '' -E 's/(require_once)/\/\/ \1/g'
