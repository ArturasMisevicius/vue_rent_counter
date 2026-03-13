# Mobile Performance

## React Native critical rules
- Use FlatList or FlashList for long lists, never ScrollView.
- Memoize list items with React.memo.
- Memoize renderItem with useCallback.
- Provide keyExtractor with stable IDs (never index).
- Provide getItemLayout when item height is fixed.
- Use removeClippedSubviews, maxToRenderPerBatch, and windowSize.

Example:
```typescript
const ListItem = React.memo(({ item }: { item: Item }) => (
  <View style={styles.item}>
    <Text>{item.title}</Text>
  </View>
));

const renderItem = useCallback(
  ({ item }: { item: Item }) => <ListItem item={item} />,
  []
);

<FlatList
  data={items}
  renderItem={renderItem}
  keyExtractor={(item) => item.id}
  getItemLayout={(data, index) => ({
    length: ITEM_HEIGHT,
    offset: ITEM_HEIGHT * index,
    index,
  })}
  removeClippedSubviews={true}
  maxToRenderPerBatch={10}
  windowSize={5}
/>
```

## Flutter critical rules
- Prefer const constructors to avoid rebuilds.
- Use targeted state (ValueListenableBuilder, Provider, Bloc, etc.).
- Avoid rebuilding expensive widgets unnecessarily.

Example:
```dart
class MyWidget extends StatelessWidget {
  const MyWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return const Column(
      children: [
        Text('Static content'),
        MyConstantWidget(),
      ],
    );
  }
}

ValueListenableBuilder<int>(
  valueListenable: counter,
  builder: (context, value, child) => Text('$value'),
  child: const ExpensiveWidget(),
)
```

## Animation performance
Fast (GPU-friendly):
- transform
- opacity

Slow (CPU-bound, avoid animating):
- width / height
- top / left / right / bottom
- margin / padding
