<?php

declare(strict_types=1);

namespace Rector\Generics\Reflection;

use Nette\Utils\Strings;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use Symplify\SimplePhpDocParser\SimplePhpDocParser;

final class ClassGenericMethodResolver
{
    /**
     * @var SimplePhpDocParser
     */
    private $simplePhpDocParser;

    public function __construct(SimplePhpDocParser $simplePhpDocParser)
    {
        $this->simplePhpDocParser = $simplePhpDocParser;
    }

    /**
     * @return array<string, string>
     */
    public function resolveFromClass(ClassReflection $classReflection): array
    {
        $genericMethodMap = [];

        $templateNames = array_keys($classReflection->getTemplateTags());

        foreach ($classReflection->getNativeMethods() as $methodReflection) {
            $parentMethodDocComment = $methodReflection->getDocComment();
            if ($parentMethodDocComment === null) {
                continue;
            }

            // how to parse?
            $parentMethodSimplePhpDocNode = $this->simplePhpDocParser->parseDocBlock($parentMethodDocComment);
            foreach ($parentMethodSimplePhpDocNode->children as $phpDocChildNode) {
                if (! $phpDocChildNode instanceof PhpDocTagNode) {
                    continue;
                }

                if (! $phpDocChildNode->value instanceof ReturnTagValueNode) {
                    continue;
                }

                foreach ($templateNames as $templateName) {
                    $typeAsString = (string) $phpDocChildNode->value->type;
                    if (! Strings::match($typeAsString, '#\b' . preg_quote($templateName, '#') . '\b#')) {
                        continue;
                    }

                    // @todo resolve params etc
                    $parameterReflections = $methodReflection->getVariants()[0]
                        ->getParameters();

                    $stringParameters = $this->resolveStringParameters($parameterReflections);
                    $genericMethodMap[$methodReflection->getName()] = [$templateName, $stringParameters];
                    continue 2;
                }
            }
        }

        return $genericMethodMap;
    }

    /**
     * @param ParameterReflection[] $parameterReflections
     * @return string[]
     */
    private function resolveStringParameters(array $parameterReflections): array
    {
        $stringParameters = [];

        foreach ($parameterReflections as $parameterReflection) {
            $stringParameters[] = $this->resolveParameterAsString($parameterReflection);
        }

        return $stringParameters;
    }

    private function resolveParameterAsString(ParameterReflection $parameterReflection): string
    {
        $parameterName = '$' . $parameterReflection->getName();
        $parameterType = $parameterReflection->getType();

        if ($parameterType instanceof MixedType && ! $parameterType->isExplicitMixed()) {
            return $parameterName;
        }

        return $parameterType->describe(VerbosityLevel::typeOnly()) . ' ' . $parameterName;
    }
}
