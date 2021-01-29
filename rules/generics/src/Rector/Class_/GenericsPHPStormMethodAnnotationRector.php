<?php

declare(strict_types=1);

namespace Rector\Generics\Rector\Class_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Symplify\SimplePhpDocParser\SimplePhpDocParser;

/**
 * @see https://github.com/phpstan/phpstan/issues/3167
 *
 * @see \Rector\Generics\Tests\Rector\Class_\GenericsPHPStormMethodAnnotationRector\GenericsPHPStormMethodAnnotationRectorTest
 */
final class GenericsPHPStormMethodAnnotationRector extends AbstractRector
{
    /**
     * @var \Rector\Generics\Reflection\ClassGenericMethodResolver
     */
    private $classGenericMethodResolver;

    public function __construct(\Rector\Generics\Reflection\ClassGenericMethodResolver $classGenericMethodResolver)
    {
        $this->classGenericMethodResolver = $classGenericMethodResolver;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Complete PHPStorm @method annotations, to make it understand the PHPStan/Psalm generics',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
/**
 * @template TEntity as object
 */
abstract class AbstractRepository
{
    /**
     * @return TEntity
     */
    public function find($id)
    {
    }
}

/**
 * @template TEntity as SomeObject
 * @extends AbstractRepository<TEntity>
 */
final class AndroidDeviceRepository extends AbstractRepository
{
}
CODE_SAMPLE

                    ,
                    <<<'CODE_SAMPLE'
/**
 * @template TEntity as object
 */
abstract class AbstractRepository
{
    /**
     * @return TEntity
     */
    public function find($id)
    {
    }
}

/**
 * @template TEntity as SomeObject
 * @extends AbstractRepository<TEntity>
 * @method SomeObject find($id)
 */
final class AndroidDeviceRepository extends AbstractRepository
{
}
CODE_SAMPLE
                ),
            ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [\PhpParser\Node\Stmt\Class_::class];
    }

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->extends === null) {
            return null;
        }

        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (! $scope instanceof Scope) {
            return null;
        }

        $classReflection = $scope->getClassReflection();
        if (! $classReflection instanceof ClassReflection) {
            return null;
        }

        $parentClassReflection = $classReflection->getParentClass();
        if (! $parentClassReflection instanceof ClassReflection) {
            return null;
        }

        if (! $parentClassReflection->isGeneric()) {
            return null;
        }

        // resolve generic method from parent

        $genericMethodMap = $this->classGenericMethodResolver->resolveFromClass(($parentClassReflection);

        $templateNames = array_keys($parentClassReflection->getTemplateTags());

        dump($genericMethodMap);
        die;

        return $node;
    }

//    /**
//     * @param string[] $templateNames
//     * @return array<string, string>
//     */
//    private function resolveParentGenericMethodMap(ClassReflection $parentClassReflection, array $templateNames): array
//    {
//        $genericMethodMap = [];
//
//        foreach ($parentClassReflection->getNativeMethods() as $methodReflection) {
//            $parentMethodDocComment = $methodReflection->getDocComment();
//            if ($parentMethodDocComment === null) {
//                continue;
//            }
//
//            // how to parse?
//            $parentMethodSimplePhpDocNode = $this->simplePhpDocParser->parseDocBlock($parentMethodDocComment);
//            foreach ($parentMethodSimplePhpDocNode->children as $phpDocChildNode) {
//                if (! $phpDocChildNode instanceof PhpDocTagNode) {
//                    continue;
//                }
//
//                if (! $phpDocChildNode->value instanceof ReturnTagValueNode) {
//                    continue;
//                }
//
//                foreach ($templateNames as $templateName) {
//                    $typeAsString = (string) $phpDocChildNode->value->type;
//                    if (! Strings::match($typeAsString, '#\b' . preg_quote($templateName, '#') . '\b#')) {
//                        continue;
//                    }
//
//                    // @todo resolve params etc
//                    $parameterReflections = $methodReflection->getVariants()[0]
//                        ->getParameters();
//
//                    $stringParameters = $this->resolveStringParameters($parameterReflections);
//                    $genericMethodMap[$methodReflection->getName()] = [$templateName, $stringParameters];
//                    continue 2;
//                }
//            }
//        }
//
//        return $genericMethodMap;
//    }

}
