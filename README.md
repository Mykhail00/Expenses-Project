- installed Clockwork for N+1 queries profiling
- modified transactions fetching query to eager load relating tables 
- optimized import transactions imports (by preloading all categories into categories array and avoiding categories
 queries for each new transaction)