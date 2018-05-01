<?php

namespace App\Http\Controllers\Admin;

use App\Article;
use App\ArticleCategory;
use App\Photo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Storage;

class ArticleController extends Controller implements CRUDMethods
{
    public function index()
    {
         return view('admin.articles.index')->
         with([
               'articles'=> Article::all(),
               'article_categories'=> ArticleCategory::all()
              ]);
    }
    public function store()
    {
        if(Article::whereId(request()->input('article_id'))->exists()){
            $article = Article::whereId(request()->input('article_id'))->first();
        }else{
            $article = new Article(['header'=>request()->input('header')]);
        }
        $article->category_id = request()->input('category_id');
        $article->header = request()->input('header');
        $article->preview = substr(request()->input('preview'),0,155);
        $article->main_text = request()->input('main_text');
        $article->save();
        $photos = Photo::whereIsAttached(false)->get();
        foreach ($photos as $photo) {
            $photo->is_attached = true;
            $photo->save();
            $nulArticle= new Article();
            $nulArticle->photos()->detach($photo);
            $article->photos()->attach($photo);
        }
        return redirect()->route('articles.index');
    }
    public function saveImages()
    {
        $image = request()->get('imageData');
        $fullName = md5(time() . uniqid()) . ".jpg";
        $path = 'images/' . $fullName;
        Storage::disk('public')->put($path, base64_decode($image));
        $photo = new Photo();
        $photo->path = Storage::url($path);
        $photo->is_attached = false;
        $photo->save();
        $article = new Article();
        $article->photos()->attach($photo);
        return response()->json($photo->path);
    }
    public function create()
    {
        $category = ArticleCategory::whereId(request()->get('id'))->firstOrFail();
        return view('admin.articles.create')->with('category', $category);
    }
    public function destroy($object)
    {
        $article = Article::whereId($object)->with('photos')->firstOrFail();
        $photos = $article->photos()->get();
        foreach ($photos as $photo){

            $photo->articles()->detach($article);
            Storage::delete($photo->path);
            Photo::destroy($photo->id);

        }

        Article::destroy($article->id);

//        dd($article);
        return redirect()->route('articles.index');



    }

    public function update($object)
    {
        // TODO: Implement update() method.
    }

    public function show($object)
    {
        // TODO: Implement show() method.
    }
    //
    public function edit($object)
    {
        $article = Article::whereId($object)->with('photos')->firstOrFail();
        $article_category = ArticleCategory::whereId($article->category_id)->firstOrFail();
        return view('admin.articles.edit')->
        with([
            'article'=> $article,
            'article_category'=> $article_category
        ]);
    }
}
