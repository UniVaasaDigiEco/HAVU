# Havu Gamification - Campus Route DEMO

## Already Done
- Created a plan for gamification demo focusing on campus route navigation
- Defined core features: map display, GPS tracking, node management, route progression
- Designed a basic demo to test our and showcase functionality
- Coding Done:
  - Implement basic map display using Leaflet.js with OpenStreetMap tiles
  - Integrate HTML5 Geolocation API for real-time user location tracking
  - Create route with 4 campus locations as nodes
  - Implement Haversine formula for distance calculation between user and nodes
  - Develop node validation logic to check if user is within proximity of a node
  - Implement route progression logic to move to the next node upon validation
  - Display user location and route on the map with markers
  - Add accuracy circle around user location
  - Create status indicator for GPS signal strength
  - Set up update interval for GPS position monitoring
- Documentation done:
  - Diagrammed architecture and feature module breakdown as well as a sequence diagram, tech stack diagram and system layers diagram
    - ArchitectureDiagram.md
      - Also SVGs:
        - ArchitectureDiagram.svg
        - FeatureModuleBreakdown.svg
        - SequenceDiagram.svg
        - TechStackDiagram.svg
        - SystemLayersDiagram.svg
  - Also made easy to understand flow diagram and user journey image
    - FlowDiagram.png
    - UserJourney.png

## TODO List - Persistent

- **KEEP DOCUMENTATION UP TO DATE**

## TODO List - Short Term

- [ ] Feedback forms
  - Player feedback to route owner
  - User feedback to UWASA
- [ ] Player stats tracking
  - How many routes completed, how many nodes collected, etc.
- [ ] Language switching system
  - EN/FI/SE
- [ ] Satellite map layer
- [ ] GDPR thing from the University
- [ ] Logos for the project and funders
- [X] Fix mobile UI
- [X] Polish UI/UX
  - [X] Add better styling to map and markers
  - [X] Improve user interface for status indicators
- [ ] Testing
  - [ ] Unit tests
  - [ ] User tests
- [ ] Add more varied content to nodes (e.g., images, descriptions)
  - [X] Add YouTube video embeds to nodes
  - [X] Add quizzes or challenges at nodes
  - [ ] Add rewards or badges for completing nodes/routes
  - [X] Add Summernote or similar WYSIWYG editor for content creation in admin panel
- [X] Add User login
- [X] Implement progress tracking and rewards system
- [X] Admin console/panel to create and manage routes and nodes
- [X] Implement SQL Database and related stuff
- [X] Ensure mobile responsiveness and performance optimization

## TODO List - Long Term

- [X] Fix the url (away from /HavuGamication)]
- [ ] Fix the YouTube video embeds (the width should be relative to the screen size/width)
- [ ] Documentation, comments, cleanup & guides
- [ ] Contact Kallion Kamut for route and content ideas
- [ ] Implement social features (leaderboards, friend challenges)
  - If time permits
- AR features
  - If time permits
- TTS if time permits
- [ ] Multi-language support if time permits
- [ ] Prepare for final presentation and demo
