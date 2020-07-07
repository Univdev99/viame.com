WITH RECURSIVE search_recursive_messages (counter, parent_counter, updated, profile_id, depth, path, cycle) AS (
        SELECT f.counter, f.parent_counter, f.updated, f.profile_id, 1,
          ARRAY[f.counter],
          false
        FROM message_messages f
      UNION ALL
        SELECT f.counter, f.parent_counter, f.updated, f.profile_id, srf.depth + 1,
          path || f.counter,
          f.counter = ANY(path)
        FROM message_messages f, search_recursive_messages srf
        WHERE f.counter = srf.parent_counter AND NOT cycle
)
SELECT f.*, srf.counter, max(updated), count(*)-1 AS replies FROM search_recursive_messages GROUP BY counter ORDER BY counter;