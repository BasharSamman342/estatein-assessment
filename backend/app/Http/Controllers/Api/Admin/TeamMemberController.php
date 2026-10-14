<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use App\Services\ImageStorage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TeamMemberController extends Controller
{
    public function __construct(private readonly ImageStorage $images) {}

    public function index(): AnonymousResourceCollection
    {
        return TeamMemberResource::collection(TeamMember::query()->ordered()->get());
    }

    public function store(TeamMemberRequest $request): TeamMemberResource
    {
        return new TeamMemberResource($this->save(new TeamMember, $request));
    }

    public function show(TeamMember $teamMember): TeamMemberResource
    {
        return new TeamMemberResource($teamMember);
    }

    public function update(TeamMemberRequest $request, TeamMember $teamMember): TeamMemberResource
    {
        return new TeamMemberResource($this->save($teamMember, $request));
    }

    public function destroy(TeamMember $teamMember): Response
    {
        $teamMember->delete();

        return response()->noContent();
    }

    private function save(TeamMember $teamMember, TeamMemberRequest $request): TeamMember
    {
        return $this->images->saveWithImage(
            $teamMember,
            $request->safe()->except('photo'),
            $request->file('photo'),
            'photo_path',
            'team',
        );
    }
}
