<?php

namespace Tito10047\TypeSafeIdBundle;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class IdEntityGenerator extends Generator {


	/**
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	private ?string $uidBaseClass;
	private ?string $uidTypeBaseClass;
	private ?string  $classNameId = null;

	public function __construct(
		private readonly Generator $generator,
	) {
	}

	public static function getControllerBaseClass(): ClassNameDetails {
		return new ClassNameDetails(AbstractController::class, '\\');
	}

	/**
	 * @param array<string,mixed> $variables
	 * @throws \Exception
	 */
	public function generateClass(string $className, string $templateName, array $variables = []): string {
		/** @var UseStatementGenerator $useGenerator */
		$useGenerator = $variables['use_statements']??null;
		if ($templateName === 'doctrine/Entity.tpl.php') {
			$templateName = __DIR__ . '/../templates/extension/maker/Entity.tpl.php';

			$this->classNameId = str_replace('\Entity\\', '\EntityId\\', $className)."Id";
			$classNameIdType = $this->classNameId."Type";
			$useStatements = new  UseStatementGenerator([]);
			if ($variables['id_type'] === EntityIdTypeEnum::UUID) {
				$useStatements->addUseStatement(Uuid::class);
			} elseif ($variables['id_type'] === EntityIdTypeEnum::ULID) {
				$useStatements->addUseStatement(Ulid::class);
			}
			$idVariables = $variables;
			$idVariables["use_statements"] = $useStatements;
			$this->generator->generateClass(
				$this->classNameId,
				 __DIR__ . '/../templates/extension/maker/TypeId.tpl.php',
				$idVariables
			);

			$typeVariables = $variables;
			$useStatements = new  UseStatementGenerator([]);
			$useStatements->addUseStatement($this->classNameId);
			$useStatements->addUseStatement(AbstractUidType::class);
			$typeVariables["use_statements"] = $useStatements;
			$parts = explode('\\', $this->classNameId);
			$typeVariables["id_class"] = array_pop($parts);;
			$this->generator->generateClass(
				$classNameIdType,
				__DIR__ . '/../templates/extension/maker/TypeIdType.tpl.php',
				$typeVariables
			);


			$variables["use_statements"]->addUseStatement($this->classNameId);
			$variables["use_statements"]->addUseStatement($classNameIdType);
		}
		if ($templateName === 'doctrine/Repository.tpl.php') {
			$templateName = __DIR__ . '/../templates/extension/maker/Repository.tpl.php';
			$variables['include_example_comments'] = false;
			$useGenerator->addUseStatement(QueryBuilder::class);
			$useGenerator->addUseStatement($this->classNameId);

		}

		return $this->generator->generateClass($className, $templateName, $variables);
	}

	/**
	 * @param array<string,mixed> $variables
	 */
	public function generateFile(string $targetPath, string $templateName, array $variables = []): void {
		$this->generator->generateFile($targetPath, $templateName, $variables);
	}

	public function dumpFile(string $targetPath, string $contents): void {
		$this->generator->dumpFile($targetPath, $contents);
	}

	public function getFileContentsForPendingOperation(string $targetPath): string {
		return $this->generator->getFileContentsForPendingOperation($targetPath);
	}

	public function createClassNameDetails(
		string $name, string $namespacePrefix, string $suffix = '', string $validationErrorMessage = ''): ClassNameDetails {
		return $this->generator->createClassNameDetails($name, $namespacePrefix, $suffix,
			$validationErrorMessage);
	}

	public function getRootDirectory(): string {
		return $this->generator->getRootDirectory();
	}

	public function hasPendingOperations(): bool {
		return $this->generator->hasPendingOperations();
	}

	public function writeChanges() {
		$this->generator->writeChanges();
	}

	public function getRootNamespace(): string {
		return $this->generator->getRootNamespace();
	}

	/**
	 * @param string[] $parameters
	 */
	public function generateController(string $controllerClassName, string $controllerTemplatePath, array $parameters = []): string {
		return $this->generator->generateController($controllerClassName, $controllerTemplatePath,
			$parameters);
	}

	/**
	 * @param array<string,mixed> $variables
	 */
	public function generateTemplate(string $targetPath, string $templateName, array $variables = []): void {
		$this->generator->generateTemplate($targetPath, $templateName, $variables);
	}

	/**
	 * @return string[]
	 */
	public function getGeneratedFiles(): array {
		return $this->generator->getGeneratedFiles();
	}



}