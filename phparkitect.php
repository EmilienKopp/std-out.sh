<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;

/**
 * Architectural rules for lecturn.
 *
 * Encodes the pragmatic CQRS layering described in ARCHITECTURE.md:
 *
 *   Write: Http -> Application (Action + Command) -> Domain -> Infrastructure (Repository) -> tables
 *   Read:  Http -> Infrastructure (Query) -> views
 *
 * Dependency direction flows inward toward Domain:
 *
 *   Domain         -> nothing (plain PHP)
 *   Application    -> Domain
 *   Infrastructure -> Domain, Models
 *   Http           -> Application, Infrastructure, Domain
 *   Listeners      -> Application, Infrastructure, Domain, Models   (infra tier)
 *   Models         -> Domain                                        (persistence detail)
 *
 * Listeners live in app/Listeners (not app/Infrastructure) for discoverability,
 * but are treated as an infrastructure-tier component: they may wire Http-less
 * side effects to Actions, repositories and models, same as the rest of infra.
 */
return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/app');

    $layeredArchitectureRules = Architecture::withComponents()
        ->component('Domain')->definedBy('App\Domain\*')
        ->component('Application')->definedBy('App\Application\*')
        ->component('Infrastructure')->definedBy('App\Infrastructure\*')
        ->component('Http')->definedBy('App\Http\*')
        ->component('Listeners')->definedBy('App\Listeners\*')
        ->component('Models')->definedBy('App\Models\*')

        // Domain is the stable core — depends on nothing but itself.
        ->where('Domain')->shouldNotDependOnAnyComponent()

        // Application (Actions, Commands) is write-side orchestration over the Domain.
        // No Models: Eloquent must never leak into an Action.
        ->where('Application')->mayDependOnComponents('Domain')

        // Infrastructure implements Domain contracts and maps Models <-> Entities.
        // Application access is for read-side Projections only ($projectionOnlyRule).
        ->where('Infrastructure')->mayDependOnComponents('Domain', 'Models', 'Application')

        // Models are a persistence detail; they may reference Domain (toEntity/HasDomainEntity)
        // and Application Projections (to{Projection}() mappers on view-backed models).
        ->where('Models')->mayDependOnComponents('Domain', 'Application')

        // Http is thin glue: call an Action or a Query, hand results to Inertia.
        // No Models: controllers never touch Eloquent directly.
        ->where('Http')->mayDependOnComponents('Application', 'Infrastructure', 'Domain')

        // Listeners are infra-tier: they may reach Actions, repositories and models.
        ->where('Listeners')->mayDependOnComponents('Application', 'Infrastructure', 'Domain', 'Models')

        ->rules();

    // Domain must stay framework-free. Collection / LengthAwarePaginator are treated as
    // pure (see ARCHITECTURE.md) so Illuminate\Support and Illuminate\Contracts are allowed;
    // Eloquent, HTTP and facades are not.
    $domainPurityRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
        ->should(new NotDependsOnTheseNamespaces([
            'Illuminate\Database',
            'Illuminate\Http',
            'Illuminate\Support\Facades',
            'App\Http',
            'App\Infrastructure',
            'App\Application',
            'App\Models',
        ]))
        ->because('the Domain layer is plain PHP: no Eloquent, HTTP or facades');

    // Application layer never queries Eloquent or touches HTTP directly.
    $applicationPurityRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application'))
        ->should(new NotDependsOnTheseNamespaces([
            'Illuminate\Database',
            'Illuminate\Http',
            'App\Models',
        ]))
        ->because('Actions accept Commands and use repository interfaces, not Eloquent or HTTP');

    // Naming conventions (ARCHITECTURE.md naming table).
    $commandNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Application\Commands'))
        ->should(new HaveNameMatching('*Command'))
        ->because('Commands are named after the Action they serve');

    // The component rules above open Application to the read side, but only for
    // Projections — Models and Infrastructure must never reach Actions or Commands.
    $projectionOnlyRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Models', 'App\Infrastructure'))
        ->should(new NotDependsOnTheseNamespaces([
            'App\Application\Actions',
            'App\Application\Commands',
        ]))
        ->because('the read side may depend on Application\Projections only, never write-side Actions or Commands');

    $queryNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Queries'))
        ->should(new HaveNameMatching('*Query'))
        ->because('read-side query objects are suffixed Query');

    $repositoryNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Infrastructure\Persistence\Repositories'))
        ->should(new HaveNameMatching('Eloquent*Repository'))
        ->because('repository implementations are Eloquent{Noun}Repository');

    $viewModelNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Models\Views'))
        ->should(new HaveNameMatching('*View'))
        ->because('view-backed read models are suffixed View');

    $controllerNamingRule = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('App\Http\Controllers'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('controllers are suffixed Controller');

    $config->add(
        $classSet,
        $domainPurityRule,
        $applicationPurityRule,
        $projectionOnlyRule,
        $commandNamingRule,
        $queryNamingRule,
        $repositoryNamingRule,
        $viewModelNamingRule,
        $controllerNamingRule,
        ...$layeredArchitectureRules,
    );
};
