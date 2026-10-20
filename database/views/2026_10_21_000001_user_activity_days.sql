CREATE VIEW
    user_activity_days AS
SELECT
    u.id AS user_id,
    u.handle AS handle,
    a.activity_date AS activity_date,
    SUM(a.rehearsal_count) AS rehearsal_count,
    SUM(a.talk_event_count) AS talk_event_count
FROM
    (
        -- Rehearsals: attributed directly when user_id was recorded, otherwise
        -- fall back to the owner of the personal team the run belongs to.
        SELECT
            COALESCE(
                r.user_id,
                (
                    SELECT
                        tm.user_id
                    FROM
                        team_members tm
                        JOIN teams t ON t.id = tm.team_id
                    WHERE
                        tm.team_id = r.team_id
                        AND tm.role = 'owner'
                        AND t.is_personal = 1
                        AND t.deleted_at IS NULL
                    LIMIT
                        1
                )
            ) AS user_id,
            DATE(r.started_at) AS activity_date,
            1 AS rehearsal_count,
            0 AS talk_event_count
        FROM
            practice_runs r
        UNION ALL
        -- Delivered talks: every live presentation session counts as a talk on
        -- stage. Sessions carry no user_id; attribute via the personal team owner.
        SELECT
            (
                SELECT
                    tm.user_id
                FROM
                    team_members tm
                    JOIN teams t ON t.id = tm.team_id
                WHERE
                    tm.team_id = s.team_id
                    AND tm.role = 'owner'
                    AND t.is_personal = 1
                    AND t.deleted_at IS NULL
                LIMIT
                    1
            ) AS user_id,
            DATE(s.started_at) AS activity_date,
            0 AS rehearsal_count,
            1 AS talk_event_count
        FROM
            presentation_sessions s
        UNION ALL
        -- Calendar talk events still count, but only on days with no recorded
        -- session for the same team, so a scheduled-then-delivered talk is not
        -- counted twice. Attributed via the personal team owner.
        SELECT
            (
                SELECT
                    tm.user_id
                FROM
                    team_members tm
                    JOIN teams t ON t.id = tm.team_id
                WHERE
                    tm.team_id = e.team_id
                    AND tm.role = 'owner'
                    AND t.is_personal = 1
                    AND t.deleted_at IS NULL
                LIMIT
                    1
            ) AS user_id,
            DATE(e.date) AS activity_date,
            0 AS rehearsal_count,
            1 AS talk_event_count
        FROM
            talk_events e
        WHERE
            NOT EXISTS (
                SELECT
                    1
                FROM
                    presentation_sessions s
                WHERE
                    s.team_id = e.team_id
                    AND DATE(s.started_at) = DATE(e.date)
            )
    ) a
    JOIN users u ON u.id = a.user_id
GROUP BY
    u.id,
    u.handle,
    a.activity_date
