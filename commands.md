
# Migracja istniejących zdjęć do struktur katalogów
```docker exec shop_php bin/console app:migrate-product-images```

# Generowanie miniatur
```docker exec shop_php bin/console app:generate-thumbnails```

# Generowanie dla konkretnego produktu
```docker exec shop_php bin/console app:generate-thumbnails --product-id=123```

# Wymuszenie regeneracji istniejących miniatur
```docker exec shop_php bin/console app:generate-thumbnails --force```
