<?php

namespace Rector\TypeDeclaration\Tests\Rector\FunctionLike\ReturnTypeDeclarationRector\Fixture\Dunglas\NullableTypes;

/**
 * @return string|null
 */
function foo($value)
{
    return $value;
}

/**
 * @return bool[]|null
 */
function bazbaz($value)
{
    return $value;
}

?>
-----
<?php

namespace Rector\TypeDeclaration\Tests\Rector\FunctionLike\ReturnTypeDeclarationRector\Fixture\Dunglas\NullableTypes;

/**
 * @return string|null
 */
function foo($value): ?string
{
    return $value;
}

/**
 * @return bool[]|null
 */
function bazbaz($value): ?array
{
    return $value;
}

?>
