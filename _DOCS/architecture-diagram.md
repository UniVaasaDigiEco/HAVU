# HAVU Gamification - System Architecture

## Architecture Overview

```mermaid
graph TB
    subgraph "Client Side - Web Browser"
        UI[User Interface]
        GPS[HTML5 Geolocation API]
        MAP[Leaflet.js Map Engine]
        LOGIC[JavaScript Logic Layer]
        
        subgraph "UI Components"
            GPS_STATUS[GPS Status Indicator]
            INFO_PANEL[Info Panel]
            PROGRESS[Progress Bar]
            MAP_VIEW[Interactive Map View]
        end
        
        subgraph "Core Libraries"
            BOOTSTRAP[Bootstrap 5.3.8]
            JQUERY[jQuery 3.7.1]
            LEAFLET[Leaflet 1.9.4]
            OSM[OpenStreetMap Tiles]
        end
    end
    
    subgraph "Server Side"
        XAMPP[XAMPP Server]
        PHP[PHP Runtime]
        INDEX[index.php]
    end
    
    subgraph "Data Layer"
        NODES[Route Nodes Array]
        CONFIG[Configuration Constants]
        STATE[Application State]
        
        subgraph "Node Properties"
            NODE_DATA[id, name, lat, lng<br/>description, visited]
        end
    end
    
    subgraph "External Services"
        OSM_API[OpenStreetMap API]
        BROWSER_GPS[Device GPS Hardware]
    end
    
    %% Server to Client
    XAMPP --> PHP
    PHP --> INDEX
    INDEX --> UI
    
    %% Library Dependencies
    BOOTSTRAP --> UI
    JQUERY --> LOGIC
    LEAFLET --> MAP
    OSM_API --> OSM
    OSM --> MAP
    
    %% UI Component Connections
    UI --> GPS_STATUS
    UI --> INFO_PANEL
    UI --> PROGRESS
    UI --> MAP_VIEW
    
    %% Map Integration
    MAP_VIEW --> MAP
    
    %% GPS Integration
    BROWSER_GPS --> GPS
    GPS --> LOGIC
    
    %% Data Flow
    NODES --> LOGIC
    CONFIG --> LOGIC
    LOGIC --> STATE
    
    %% Logic to UI Updates
    LOGIC --> GPS_STATUS
    LOGIC --> INFO_PANEL
    LOGIC --> PROGRESS
    LOGIC --> MAP
    
    %% Map Rendering
    MAP --> MAP_VIEW
    
    style UI fill:#e1f5ff
    style LOGIC fill:#fff4e1
    style NODES fill:#e8f5e9
    style MAP fill:#f3e5f5
    style XAMPP fill:#ffe0b2
```

## Component Flow Diagram

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant GPS_API
    participant App_Logic
    participant Leaflet_Map
    participant OSM_Server
    participant Data_Store
    
    User->>Browser: Access Application URL
    Browser->>XAMPP: HTTP Request
    XAMPP->>Browser: Serve index.php
    Browser->>App_Logic: Initialize Application
    
    App_Logic->>Data_Store: Load Route Nodes
    Data_Store-->>App_Logic: Return 4 Campus Locations
    
    App_Logic->>Leaflet_Map: Initialize Map
    Leaflet_Map->>OSM_Server: Request Map Tiles
    OSM_Server-->>Leaflet_Map: Return Tiles
    
    App_Logic->>Leaflet_Map: Draw Route Line
    App_Logic->>Leaflet_Map: Add Node Markers
    
    App_Logic->>GPS_API: Request Location Permission
    GPS_API->>User: Permission Prompt
    User-->>GPS_API: Grant Permission
    
    loop Every 3 seconds
        GPS_API->>App_Logic: Send GPS Coordinates
        App_Logic->>App_Logic: Calculate Distance to Nodes
        App_Logic->>Leaflet_Map: Update User Marker
        
        alt Within 50m of Node
            App_Logic->>Leaflet_Map: Open Node Popup
            App_Logic->>UI: Update Info Panel (Nearby Alert)
        else Not Near Node
            App_Logic->>UI: Show Distance to Nearest
        end
    end
    
    User->>Leaflet_Map: Click "Mark as Visited"
    Leaflet_Map->>App_Logic: Mark Node Visited
    App_Logic->>Data_Store: Update Node State
    App_Logic->>Leaflet_Map: Change Marker Icon
    App_Logic->>UI: Update Progress Bar
    
    alt All Nodes Visited
        App_Logic->>User: Show Completion Message
    end
```

## Data Structure

```mermaid
classDiagram
    class RouteNode {
        +int id
        +string name
        +float lat
        +float lng
        +string description
        +boolean visited
    }
    
    class Configuration {
        +int PROXIMITY_THRESHOLD = 50
        +int UPDATE_INTERVAL = 3000
        +array CAMPUS_CENTER
    }
    
    class Marker {
        +LatLng position
        +Icon icon
        +string title
        +Popup popup
        +setIcon()
        +openPopup()
        +closePopup()
    }
    
    class UserPosition {
        +float lat
        +float lng
        +float accuracy
        +timestamp timestamp
    }
    
    class ApplicationState {
        +array routeNodes
        +object markers
        +Marker userMarker
        +UserPosition userPosition
        +Polyline routeLine
    }
    
    RouteNode "4" --* ApplicationState
    Marker "4" --* ApplicationState
    UserPosition "1" --* ApplicationState
    Configuration ..> ApplicationState : configures
```

## System Layers

```mermaid
graph LR
    subgraph "Presentation Layer"
        A[HTML/CSS]
        B[Bootstrap UI]
        C[Leaflet Map View]
    end
    
    subgraph "Application Layer"
        D[GPS Tracking]
        E[Distance Calculation]
        F[Proximity Detection]
        G[Progress Tracking]
        H[State Management]
    end
    
    subgraph "Business Logic"
        I[Haversine Formula]
        J[Node Validation]
        K[Route Progression]
    end
    
    subgraph "Data Layer"
        L[Route Nodes Array]
        M[Marker Objects]
        N[User State]
    end
    
    subgraph "External APIs"
        O[HTML5 Geolocation]
        P[OpenStreetMap]
    end
    
    A --> D
    B --> G
    C --> E
    
    D --> I
    E --> I
    F --> J
    G --> K
    
    I --> L
    J --> L
    K --> N
    
    D --> O
    C --> P
    
    H --> M
    H --> N
```

## Feature Module Breakdown

```mermaid
mindmap
    root((HAVU Gamification))
        Map System
            Leaflet.js Integration
            OpenStreetMap Tiles
            Route Line Drawing
            Marker Management
            User Location Display
        GPS Tracking
            HTML5 Geolocation API
            Position Monitoring
            Accuracy Circle
            Status Indicator
            Update Interval
        Node Management
            4 Campus Locations
                Ankkuri Palosaari
                Technobothnia
                Tritonia Library
                Tervahovi Building
            Node Properties
            Visit Tracking
            Icon States
        Proximity System
            Distance Calculation
            Haversine Formula
            50m Threshold
            Auto Popup
            Nearby Detection
        Progress Tracking
            Visit Counter
            Progress Bar
            Completion Check
            Celebration Messages
        User Interface
            GPS Status Panel
            Info Panel
            Progress Container
            Interactive Map
            Bootstrap Styling
```

## Technology Stack

```mermaid
graph TB
    subgraph "Frontend Framework"
        HTML5[HTML5]
        CSS3[CSS3]
        JS[JavaScript ES6]
    end
    
    subgraph "UI Framework"
        BS[Bootstrap 5.3.8]
    end
    
    subgraph "JavaScript Libraries"
        JQ[jQuery 3.7.1]
        LF[Leaflet 1.9.4]
    end
    
    subgraph "Server"
        XAMPP[XAMPP]
        PHP[PHP]
    end
    
    subgraph "APIs & Services"
        GEO[HTML5 Geolocation API]
        OSM[OpenStreetMap API]
    end
    
    subgraph "Package Management"
        NPM[NPM]
    end
    
    HTML5 --> JS
    CSS3 --> BS
    JS --> JQ
    JS --> LF
    JQ --> LF
    
    NPM --> BS
    NPM --> JQ
    NPM --> LF
    
    PHP --> HTML5
    XAMPP --> PHP
    
    JS --> GEO
    LF --> OSM
    
    style HTML5 fill:#e44d26
    style CSS3 fill:#1572b6
    style JS fill:#f7df1e
    style BS fill:#7952b3
    style LF fill:#199900
    style PHP fill:#777bb4
    style XAMPP fill:#fb7a24
```

## Deployment Architecture

```mermaid
graph TB
    subgraph "Development Environment"
        DEV[Developer Machine]
        CODE[Source Code]
        NPM_INSTALL[npm install]
    end
    
    subgraph "Local Server - XAMPP"
        APACHE[Apache HTTP Server]
        PHP_ENGINE[PHP Engine]
        HTDOCS[htdocs/HavuGamification/]
    end
    
    subgraph "Application Files"
        INDEX[index.php]
        PKG[package.json]
        NODE_MODULES[node_modules/]
        DOCS[_DOCS/]
    end
    
    subgraph "Client Access"
        BROWSER[Web Browser]
        URL[localhost/HavuGamification/]
        GPS_DEVICE[GPS-enabled Device]
    end
    
    subgraph "External Resources"
        OSM_TILES[OpenStreetMap Tiles]
        CDN[Map Data CDN]
    end
    
    CODE --> NPM_INSTALL
    NPM_INSTALL --> NODE_MODULES
    CODE --> INDEX
    CODE --> PKG
    
    INDEX --> HTDOCS
    NODE_MODULES --> HTDOCS
    DOCS --> HTDOCS
    
    HTDOCS --> APACHE
    APACHE --> PHP_ENGINE
    
    BROWSER --> URL
    URL --> APACHE
    GPS_DEVICE --> BROWSER
    
    BROWSER --> OSM_TILES
    OSM_TILES --> CDN
    
    style DEV fill:#e3f2fd
    style APACHE fill:#d7191c
    style BROWSER fill:#4caf50
    style OSM_TILES fill:#2196f3
```


