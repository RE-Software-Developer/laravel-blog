<?php

namespace BinshopsBlog\Controllers;

use App\Http\Controllers\Controller;
use BinshopsBlog\Baum\MoveNotPossibleException;
use BinshopsBlog\Models\BinshopsPostTranslation;
use Illuminate\Http\Request;
use BinshopsBlog\Events\CategoryAdded;
use BinshopsBlog\Events\CategoryEdited;
use BinshopsBlog\Events\CategoryWillBeDeleted;
use BinshopsBlog\Helpers;
use BinshopsBlog\Middleware\LoadLanguage;
use BinshopsBlog\Middleware\UserCanManageBlogPosts;
use BinshopsBlog\Models\BinshopsCategory;
use BinshopsBlog\Models\BinshopsCategoryTranslation;
use BinshopsBlog\Models\BinshopsLanguage;
use BinshopsBlog\Requests\DeleteBinshopsBlogCategoryRequest;
use BinshopsBlog\Requests\StoreBinshopsBlogCategoryRequest;
use BinshopsBlog\Requests\UpdateBinshopsBlogCategoryRequest;

/**
 * Class BinshopsCategoryAdminController
 * @package BinshopsBlog\Controllers
 */
class BinshopsCategoryAdminController extends Controller
{
    /**
     * BinshopsCategoryAdminController constructor.
     */
    public function __construct()
    {
        $this->middleware(UserCanManageBlogPosts::class);
        $this->middleware(LoadLanguage::class);

    }

    /**
     * Show list of categories
     *
     * @return mixed
     */
    public function index(Request $request){
        $language_id = $request->get('language_id');
        $categories = BinshopsCategoryTranslation::orderBy("category_id")->where('lang_id', $language_id)->paginate(25);
        return view("binshopsblog_admin::categories.index",[
            'categories' => $categories,
            'language_id' => $language_id
        ]);
    }

    /**
     * Show the search results for $_GET['s']
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function searchCategories(Request $request)
    {
        $search = $request->get("s");
        $language_id = $request->get('language_id');
        $categories = BinshopsCategoryTranslation::orderBy("category_id")
            ->where('lang_id', $language_id)
            ->where(function($query) use ($search) {
                $query
                    ->where('category_name', 'LIKE', "%$search%")
                    ->orWhere('category_description', 'LIKE', "%$search%")
                    ->orWhere('slug', 'LIKE', "%$search%");
            })
            ->paginate(25);

        return view("binshopsblog_admin::categories.index",[
            'categories' => $categories,
            'language_id' => $language_id
        ]);
    }

    /**
     * Show all posts of the category
     * @param $categoryId
     * @return mixed
     */
    public function category_posts($categoryId, Request $request){
        $language_id = $request->get('language_id');

        $category = BinshopsCategory::findOrFail($categoryId);
        $cat_trans = BinshopsCategoryTranslation::where(
            [
                ['lang_id', '=', $language_id],
                ['category_id', '=', $categoryId]
            ]
        )->first();

        $postIds = $category->posts()
            ->where("binshops_post_categories.category_id", $category->id)
            ->pluck('binshops_posts.id');

        $posts = BinshopsPostTranslation::join('binshops_posts', 'binshops_post_translations.post_id', '=', 'binshops_posts.id')
            ->where('lang_id', $language_id)
            ->whereIn('binshops_posts.id', $postIds)
            ->paginate(10);

        return view("binshopsblog_admin::categories.category_posts",[
            'category' => $category,
            'category_translation' => $cat_trans,
            'post_translations' => $posts,
            'language_id' => $language_id,
        ]);
    }

    /**
     * Show the form for creating new category
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create_category(Request $request){
        $language_id = $request->get('language_id');
        $language_list = BinshopsLanguage::where('active',true)->get();

        $cat_list = BinshopsCategory::whereHas('categoryTranslations', function ($query) use ($language_id) {
            return $query->where('lang_id', '=', $language_id);
        })->get();

        $rootList = BinshopsCategory::roots()->get();
        BinshopsCategory::loadSiblingsWithList($rootList);


        return view("binshopsblog_admin::categories.add_category",[
            'category' => new \BinshopsBlog\Models\BinshopsCategory(),
            'category_translation' => new \BinshopsBlog\Models\BinshopsCategoryTranslation(),
            'category_tree' => $cat_list,
            'cat_roots' => $rootList,
            'language_id' => $language_id,
            'language_list' => $language_list
        ]);
    }

    /**
     * Store a new category
     *
     * @param StoreBinshopsBlogCategoryRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * This controller is totally REST controller
     */
    public function store_category(Request $request){
        $language_id = $request->get('language_id');
        $language_list = $request['data'];

        if ($request['parent_id']== 0){
            $request['parent_id'] = null;
        }
        $new_category = BinshopsCategory::create([
            'parent_id' => $request['parent_id']
        ]);

        foreach ($language_list as $key => $value) {
            if ($value['lang_id'] != -1 && $value['category_name'] !== null){
                //check for slug availability
                $obj = BinshopsCategoryTranslation::where('slug',$value['slug'])->first();
                if ($obj){
                    BinshopsCategory::destroy($new_category->id);
                    return response()->json([
                        'code' => 403,
                        'message' => "slug is already taken",
                        'data' => $value['lang_id']
                    ]);
                }
                $new_category_translation = $new_category->categoryTranslations()->create([
                    'category_name' => $value['category_name'],
                    'slug' => $value['slug'],
                    'category_description' => $value['category_description'],
                    'lang_id' => $value['lang_id'],
                    'category_id' => $new_category->id
                ]);
            }
        }

        event(new CategoryAdded($new_category, $new_category_translation));
        Helpers::flash_message("Saved new category");
        return response()->json([
            'code' => 200,
            'message' => "category successfully aaded"
        ]);
    }

    /**
     * Show the edit form for category
     * @param $categoryId
     * @return mixed
     */
    public function edit_category($categoryId, Request $request){
        $language_id = $request->get('language_id');
        $language_list = BinshopsLanguage::where('active',true)->get();

        $category = BinshopsCategory::findOrFail($categoryId);
        $cat_trans = BinshopsCategoryTranslation::where(
            [
                ['lang_id', '=', $language_id],
                ['category_id', '=', $categoryId]
            ]
        )->first();

        return view("binshopsblog_admin::categories.edit_category",[
            'category' => $category,
            'category_translation' => $cat_trans,
            'categories_list' => BinshopsCategoryTranslation::orderBy("category_id")->where('lang_id', $language_id)->get(),
            'language_id' => $language_id,
            'language_list' => $language_list
        ]);
    }

    /**
     * Save submitted changes
     *
     * @param UpdateBinshopsBlogCategoryRequest $request
     * @param $categoryId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update_category(UpdateBinshopsBlogCategoryRequest $request, $categoryId){
        /** @var BinshopsCategory $category */
        $category = BinshopsCategory::findOrFail($categoryId);
        $language_id = $request->get('language_id');
        $translation = BinshopsCategoryTranslation::where(
            [
                ['lang_id', '=', $language_id],
                ['category_id', '=', $categoryId]
            ]
        )->first();
        $category->fill($request->all());
        $translation->fill($request->all());
        
        // if the parent_id is passed in as 0 it will create an error
        if ($category->parent_id <= 0) {
            $category->parent_id = null;
        }
        
        $category->save();
        $translation->save();
        Helpers::flash_message("Saved category changes");

        if ($request->has('children')) {
            $childrenIds = array_filter(explode(',', $request->children), function($c) { return $c !== ''; });
            $categoryChildrenIds = $category->children()->pluck('id')->toArray();

            if ($childrenIds) {
                //if the children id set is outdated, don't reorder and show an error message
                if (array_diff($childrenIds, $categoryChildrenIds) || array_diff($categoryChildrenIds, $childrenIds)) {
                    Helpers::flash_message("Saved category changes, but children data was outdated. If you tried to reorder children, please refresh the page and try again.");
                } else {
                    $category->reorderChildren($childrenIds);
                }
            }
        }

        event(new CategoryEdited($category));
        return redirect($translation->edit_url());
    }

    /**
     * Delete the category
     *
     * @param DeleteBinshopsBlogCategoryRequest $request
     * @param $categoryId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function destroy_category(DeleteBinshopsBlogCategoryRequest $request, $categoryId){

        /* Please keep this in, so code inspectiwons don't say $request was unused. Of course it might now get marked as left/right parts are equal */
        $request=$request;

        $category = BinshopsCategory::findOrFail($categoryId);
        $children = $category->children()->get();
        if (sizeof($children) > 0) {
            Helpers::flash_message("This category could not be deleted it has some sub-categories. First try to change parent category of subs.");
            return redirect(route('binshopsblog.admin.categories.index'));
        }

        event(new CategoryWillBeDeleted($category));
        $category->delete();

        Helpers::flash_message("Category successfully deleted!");
        return redirect( route('binshopsblog.admin.categories.index') );
    }

    /**
     * Reorder root categories
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder_root_categories(Request $request)
    {
        if ($request->has('children')) {
            $categoryIds = array_filter(explode(',', $request->children), function($c) { return $c !== ''; });
            $existingCategoryIds = BinshopsCategory::where('depth', 0)->pluck('id')->toArray();

            if ($categoryIds) {
                //if the children id set is outdated, don't reorder and show an error message
                if (array_diff($categoryIds, $existingCategoryIds) || array_diff($existingCategoryIds, $categoryIds)) {
                    Helpers::flash_message("Children data is outdated. Please refresh the page and try again.");
                } else {
                    //Reorder children (Loop through children, and move each one to the right of the previous one)
                    foreach ($categoryIds as $childId) {
                        $child = BinshopsCategory::find($childId);
                        try {
                            if (isset($leftSibling) && $leftSibling) {
                                $child->moveToRightOf($leftSibling);
                            }
                            $leftSibling = $child;
                            event(new CategoryEdited($child));
                        } catch (MoveNotPossibleException $e) {
                            //Do Nothing
                        }
                    }
                }
            }
        }

        Helpers::flash_message("Successfully Reordered Root Categories");
        return redirect( route('binshopsblog.admin.categories.index') );
    }

}
