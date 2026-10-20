export type CalendarDayActivity = {
rehearsals: number,
talks: number,
};
export type ContactCard = {
id: number,
name: string,
avatar: string,
handle: string | null,
social_x_handle: string | null,
social_github_handle: string | null,
};
export type ContactConnection = {
id: number,
name: string,
avatar: string,
handle: string | null,
social_x_handle: string | null,
social_github_handle: string | null,
followed_at: string | null,
};
export type CursorPaginatedDataCollection<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = CursorPaginator<TKey, TValue>;
export type DeckSummary = {
id: number,
name: string,
is_private: boolean,
slide_count: number,
status: string,
draft_error: string | null,
updated_at: string | null,
talk_id: number | null,
version: string | null,
background: string | null,
background_image: string | null,
};
export type DeckVersion = {
id: number,
version: string | null,
name: string,
updated_at: string | null,
current: boolean,
};
export type EditorDeck = {
id: number,
name: string,
is_private: boolean,
content: Record<string, any>,
talk_settings: Record<string, any>,
flow: Record<string, any> | null,
source: Record<string, any>,
updated_at: string | null,
version: string | null,
versions: DeckVersion[],
};
export type FollowableProfile = {
id: number,
handle: string,
name: string,
avatar: string,
talks_count: number,
total_viewers: number,
total_reactions: number,
followers_count: number,
following_count: number,
follow_status: string,
social_x_handle: string | null,
social_github_handle: string | null,
};
export type FollowedTalk = {
id: number,
name: string,
updated_at: string | null,
last_presented_at: string | null,
viewer_count: number,
reaction_total: number,
user: ContactCard,
};
export type IncomingReviewRequest = {
id: number,
practice_run_id: number,
presentation_name: string,
requester_name: string,
requester_avatar: string | null,
status: string,
rehearsed_at: string,
duration_seconds: number,
requested_at: string | null,
};
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type LibraryImage = {
url: string,
name: string,
presentation_id: number,
presentation_name: string,
created_at: string | null,
};
export type OutgoingReviewRequest = {
id: number,
practice_run_id: number,
presentation_name: string,
reviewer_name: string,
reviewer_avatar: string | null,
status: string,
rehearsed_at: string,
duration_seconds: number,
requested_at: string | null,
};
export type PaginatedDataCollection<TKey, TValue> = LengthAwarePaginator<TKey, TValue>;
export type PlatformOverview = {
total_users: number,
total_teams: number,
total_workspaces: number,
total_presentations: number,
total_sessions: number,
live_sessions: number,
total_reactions: number,
total_viewers: number,
};
export type PresentationEmbed = {
embed_token: string,
content: Record<string, any>,
flow: Record<string, any> | null,
};
export type PresenterDeck = {
id: number,
name: string,
is_private: boolean,
content: Record<string, any>,
talk_settings: Record<string, any>,
flow: Record<string, any> | null,
source: Record<string, any>,
embed_token: string,
updated_at: string | null,
yoyotranslate: undefined,
};
export type Projection = object;
export type PublicSlideshow = {
name: string,
content: Record<string, any>,
flow: Record<string, any> | null,
source: Record<string, any>,
};
export type RecentSession = {
id: number,
presentation_id: number,
presentation_name: string,
started_at: string,
ended_at: string | null,
duration_seconds: number,
is_live: boolean,
viewer_count: number,
reaction_total: number,
reaction_counts: Record<string, number>,
top_emoji: string | null,
};
export type RehearsalReplay = {
id: number,
presentation_id: number,
presentation_name: string,
started_at: string,
ended_at: string,
duration_seconds: number,
slide_timings: {
slide: number,
seconds: number,
}[],
step_events: {
at_ms: number,
slide: number,
step: number,
}[],
has_recording: boolean,
content: Record<string, any>,
flow: Record<string, any> | null,
version: string | null,
};
export type RehearsalReviewDetail = {
id: number,
practice_run_id: number,
presentation_id: number,
status: string,
presentation_name: string,
requester_name: string,
requester_avatar: string | null,
reviewer_name: string,
reviewer_avatar: string | null,
rehearsed_at: string,
duration_seconds: number,
content: Record<string, any>,
flow: Record<string, any> | null,
step_events: {
at_ms: number,
slide: number,
step: number,
}[],
has_recording: boolean,
comments: ReviewComment[],
};
export type ReviewComment = {
id: number,
slide_number: number,
message: string,
created_at: string | null,
};
export type ReviewSummary = {
id: number,
reviewer_name: string,
reviewer_avatar: string | null,
status: string,
requested_at: string | null,
};
export type ReviewableBetaRequest = {
id: number,
name: string,
email: string,
message: string | null,
status: string,
created_at: string | null,
registered: boolean,
};
export type ReviewedRun = {
practice_run_id: number,
rehearsed_at: string,
duration_seconds: number,
reviews: ReviewSummary[],
};
export type RunReview = {
id: number,
reviewer_user_id: number,
reviewer_name: string,
reviewer_avatar: string | null,
status: string,
requested_at: string | null,
comments: ReviewComment[],
};
export type ScheduledTalkEvent = {
id: number,
name: string,
date: string,
start_time: string | null,
talk_id: number | null,
talk_title: string | null,
};
export type SessionDetail = {
id: number,
presentation_id: number,
presentation_name: string,
started_at: string,
ended_at: string | null,
is_live: boolean,
duration_seconds: number,
viewer_count: number,
reaction_total: number,
reaction_counts: Record<string, number>,
word_count: number | null,
slide_timings: {
slide: number,
seconds: number,
}[],
reaction_slides: {
slide: number,
reactions: Record<string, number>,
}[],
};
export type TeamEngagementSummary = {
total_sessions: number,
total_reactions: number,
total_viewers: number,
avg_reactions_per_session: number,
top_emoji: string | null,
};
export type TeamRehearsalHistory = {
id: number,
presentation_id: number,
presentation_name: string,
started_at: string,
duration_seconds: number,
slide_count: number,
slide_timings: {
slide: number,
seconds: number,
}[],
version: string | null,
};
export type TimelineEntry = {
type: string,
id: number,
presentation_id: number | null,
presentation_name: string,
started_at: string,
duration_seconds: number,
is_live: boolean,
viewer_count: number,
reaction_total: number,
reaction_counts: Record<string, number>,
event_name: string | null,
};
export type UserFollowRequest = {
id: number,
name: string,
avatar: string,
handle: string | null,
social_x_handle: string | null,
social_github_handle: string | null,
requested_at: string,
};
