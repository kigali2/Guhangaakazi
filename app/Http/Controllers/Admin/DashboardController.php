<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Article,BusinessIdea,Comment,ContactMessage,Opportunity,Photo,Skill,Video};
class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $open = Opportunity::published()->where(fn ($q) => $q->whereNull("closes_at")->orWhere("closes_at", ">=", $today))->where(fn ($q) => $q->whereNull("opens_at")->orWhere("opens_at", "<=", $today))->count();
        $sum = fn ($c) => collect([BusinessIdea::class, Skill::class, Opportunity::class, Article::class, Photo::class, Video::class])->sum(fn ($m) => (int) $m::sum($c));
        $stats = [
            ["Business ideas", BusinessIdea::count(), "primary"],
            ["Published ideas", BusinessIdea::published()->count(), "success"],
            ["Draft ideas", BusinessIdea::where("status", "draft")->count(), "secondary"],
            ["Skills", Skill::count(), "primary"],
            ["Opportunities", Opportunity::count(), "primary"],
            ["Open opportunities", $open, "success"],
            ["Articles", Article::count(), "primary"],
            ["Photos", Photo::count(), "primary"],
            ["Videos", Video::count(), "primary"],
            ["Unread messages", ContactMessage::whereNull("read_at")->count(), "warning"],
            ["Total views", $sum("views_count"), "info"],
            ["Total likes", $sum("likes_count"), "danger"],
            ["Total comments", Comment::where("status", "approved")->count(), "dark"],
        ];
        $list = fn ($q) => $q->latest()->take(5)->get()->map(fn ($r) => ["title" => $r->title, "at" => $r->created_at, "badge" => $r->status]);
        $recent = [
            "Recent business ideas" => $list(BusinessIdea::query()),
            "Recent articles" => $list(Article::query()),
            "Recent photos" => $list(Photo::query()),
            "Recent videos" => $list(Video::query()),
            "Recent contact messages" => ContactMessage::latest()->take(5)->get()->map(fn ($m) => ["title" => $m->subject . " (" . $m->name . ")", "at" => $m->created_at, "badge" => $m->read_at ? "read" : "unread"]),
        ];
        return view("admin.dashboard", compact("stats", "recent"));
    }
}
