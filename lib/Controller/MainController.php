<?php
namespace OCA\Cookbook\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCA\Cookbook\Service\RecipeService;
use OCA\Cookbook\Service\DbCacheService;

class MainController extends Controller
{
    protected $appName;

    /**
     * @var RecipeService
     */
    private $service;
    /**
     * @var DbCacheService
     */
    private $dbCacheService;
    /**
     * @var IURLGenerator
     */
    private $urlGenerator;

    public function __construct(string $AppName, IRequest $request, RecipeService $recipeService, DbCacheService $dbCacheService, IURLGenerator $urlGenerator)
    {
        parent::__construct($AppName, $request);

        $this->service = $recipeService;
        $this->urlGenerator = $urlGenerator;
        $this->appName = $AppName;
        $this->dbCacheService = $dbCacheService;
    }

    /**
     * Load the start page of the app.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        $this->dbCacheService->triggerCheck();
        
        $view_data = [
            'all_keywords' => $this->service->getAllKeywordsInSearchIndex(),
            'folder' => $this->service->getUserFolderPath(),
            'update_interval' => $this->dbCacheService->getSearchIndexUpdateInterval(),
            'last_update' => $this->dbCacheService->getSearchIndexLastUpdateTime(),
            'print_image' => $this->service->getPrintImage(),
        ];

        return new TemplateResponse($this->appName, 'index', $view_data);  // templates/index.php
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function categories()
    {
        $this->dbCacheService->triggerCheck();
        
		$categories = $this->service->getAllCategoriesInSearchIndex();
        return new DataResponse($categories, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function keywords()
    {
        $this->dbCacheService->triggerCheck();
        
		$keywords = $this->service->getAllKeywordsInSearchIndex();
        return new DataResponse($keywords, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function home()
    {
        $this->dbCacheService->triggerCheck();
        
        try {
			$recipes = $this->service->getAllRecipesInSearchIndex();

			foreach ($recipes as $i => $recipe) {
                $recipes[$i]['image_url'] = $this->urlGenerator->linkToRoute(
                    'cookbook.recipe.image',
                    [
                        'id' => $recipe['recipe_id'],
                        'size' => 'thumb',
                        't' => $this->service->getRecipeMTime($recipe['recipe_id'])
                    ]
                );
			}

			$response = new TemplateResponse($this->appName, 'content/search', ['recipes' => $recipes]);
            $response->renderAs('blank');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function error()
    {
        $this->dbCacheService->triggerCheck();
        
        $response = new TemplateResponse($this->appName, 'navigation/error');
        $response->renderAs('blank');

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function search($query)
    {
		$this->dbCacheService->triggerCheck();
		
        $query = urldecode($query);
        try {
			$recipes = $this->service->findRecipesInSearchIndex($query);

			foreach ($recipes as $i => $recipe) {
                $recipes[$i]['imageUrl'] = $this->urlGenerator->linkToRoute(
                    'cookbook.recipe.image',
                    [
                        'id' => $recipe['recipe_id'],
                        'size' => 'thumb',
                        't' => $this->service->getRecipeMTime($recipe['recipe_id'])
                    ]
                );
			}

            return new DataResponse($recipes, 200, ['Content-Type' => 'application/json']);
            // TODO: Remove obsolete code below when this is ready
			$response = new TemplateResponse($this->appName, 'content/search', ['query' => $query, 'recipes' => $recipes]);
            $response->renderAs('blank');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function category($category)
    {
        $this->dbCacheService->triggerCheck();
        
        $category = urldecode($category);
        try {
			$recipes = $this->service->getRecipesByCategory($category);
			foreach ($recipes as $i => $recipe) {
                $recipes[$i]['imageUrl'] = $this->urlGenerator->linkToRoute(
                    'cookbook.recipe.image',
                    [
                        'id' => $recipe['recipe_id'],
                        'size' => 'thumb',
                        't' => $this->service->getRecipeMTime($recipe['recipe_id'])
                    ]
                );
			}

            return new DataResponse($recipes, Http::STATUS_OK, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function tag($tag)
    {
        $this->dbCacheService->triggerCheck();
        
        $tag = urldecode($tag);
        try {
			$recipes = $this->service->getRecipesByTag($tag);
			foreach ($recipes as $i => $recipe) {
                $recipes[$i]['imageUrl'] = $this->urlGenerator->linkToRoute(
                    'cookbook.recipe.image',
                    [
                        'id' => $recipe['recipe_id'],
                        'size' => 'thumb',
                        't' => $this->service->getRecipeMTime($recipe['recipe_id'])
                    ]
                );
			}

            return new DataResponse($recipes, Http::STATUS_OK, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function recipe($id)
    {
        $this->dbCacheService->triggerCheck();
        
        try {
            $recipe = $this->service->getRecipeById($id);
            $recipe['image_url'] = $this->urlGenerator->linkToRoute(
                'cookbook.recipe.image',
                [
                    'id' => $id,
                    'size' => 'full',
                    't' => $recipe['dateModified']
                ]
            );
            $recipe['id'] = $id;
            $recipe['print_image'] = $this->service->getPrintImage();
            $response = new TemplateResponse($this->appName, 'content/recipe_vue', $recipe);
            $response->renderAs('blank');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create()
    {
        $this->dbCacheService->triggerCheck();
        
        try {
            $recipe = [];

            $response = new TemplateResponse($this->appName, 'content/edit', $recipe);
            $response->renderAs('blank');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
	}

    /**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function import()
	{
	    $this->dbCacheService->triggerCheck();
	    
        if (!isset($_POST['url'])) {
            return new DataResponse('Field "url" is required', 400);
        }

        try {
            $recipe_file = $this->service->downloadRecipe($_POST['url']);
            $recipe_json = $this->service->parseRecipeFile($recipe_file);
            $this->dbCacheService->addRecipe($recipe_file);

            return new DataResponse($recipe_json, Http::STATUS_OK, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function new()
	{
		$this->dbCacheService->triggerCheck();
		
	    try {
	        $recipe_data = $_POST;
			$file = $this->service->addRecipe($recipe_data);
			$this->dbCacheService->addRecipe($file);

			return new DataResponse($file->getParent()->getId());
		} catch (\Exception $e) {
			return new DataResponse($e->getMessage(), 500);
		}
	}

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function edit($id)
    {
        $this->dbCacheService->triggerCheck();
        
        try {
            $recipe = [];

            if ($id !== null) {
                $recipe = $this->service->getRecipeById($id);

                if(!$recipe) { throw new \Exception('Recipe ' . $id . ' not found'); }

                $recipe['id'] = $id;
            }

            $response = new TemplateResponse($this->appName, 'content/edit', $recipe);
            $response->renderAs('blank');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse($e->getMessage(), 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function update($id)
    {
        $this->dbCacheService->triggerCheck();
        
		try {
	        $recipe_data = [];

            parse_str(file_get_contents("php://input"), $recipe_data);

            $recipe_data['id'] = $id;

	        $file = $this->service->addRecipe($recipe_data);
	        $this->dbCacheService->addRecipe($file);
			
            return new DataResponse($id);

		} catch (\Exception $e) {
			return new DataResponse($e->getMessage(), 500);

        }
    }
}
